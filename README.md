# Directus Eloquent Repository

This package implements a repository pattern that enables the lazy loading of data from Directus into Eloquent models
within a Laravel application.

## Requirements

- PHP 8.1 or higher
- Laravel 11 or higher
- Directus 10 or higher
- Models must properly configure the `$fillable` property to ensure that mass assignment is handled safely.

## Installation

```bash
composer require drderpling/eloquent-directus-repository
```

## Usage

### Create a New Repository

To create a new repository, extend the `EloquentDirectusRepository` in a new class and implement the `getContext()`
method to return a configured `Context` object. This object specifies the model and fields to be loaded.

Example of a basic repository setup:

```php
use DrDerpling\DirectusRepository\EloquentDirectusRepository;

class MyRepository extends EloquentDirectusRepository {
    public function getContext(): Context {
        return ContextFactory::create(MyModel::class);
    }
}
````

### Context Object

In order for the repository to know which model to load we need to provide a context. The context is an object that
defines the model that should be loaded. This object is created using the `ContextFactory::create()` method. This method
takes the model class as a parameter.

```php
use DrDerpling\DirectusRepository\Context\ContextFactory;
use DrDerpling\DirectusRepository\Context\Context;

class MyRepository extends EloquentDirectusRepository
{
      public function getContext(): Context
    {
        return ContextFactory::create(MyModel::class);
    }
}
```

#### Options

The `ContextFactory::create()` method has 4 parameters. Ill break down each parameter below:

#### Options for `ContextFactory::create()`

| Parameter         | Description                                                                                                                                  |
|-------------------|----------------------------------------------------------------------------------------------------------------------------------------------|
| `$modelClass`     | The class of the model to be loaded. Required.                                                                                               |
| `$fields`         | An array of fields to be loaded. If empty, all fields will be loaded. Optional.                                                              |
| `$collectionName` | The name of the collection to be loaded. If empty, the repository will generate the collection name based on the model class. Optional.      |
| `$orderBy`        | The field used to order the records. If empty, the repository will use the `sort` field. Setting this to `null` disables ordering. Optional. |


### Adding the `$fillable` property to the model

In order for the repository to be able to save data to the database, the model must have a `$fillable` property. This
property should be an array of fields that are allowed to be mass assigned. This is a security feature to prevent
unwanted fields from being saved to the database.

```php
class MyModel extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];
}
```

For more information on the `$fillable` property, see the Laravel documentation: https://laravel.com/docs/11.x/eloquent#mass-assignment

### Methods

#### getList()

To get a list of records, you can call the `getList()` method. This will return a collection of records. similar to
the `all()` method in eloquent.

```php
$repository = new MyRepository();

$records = $repository->getList();
```

#### get()

To get a single record, you can call the `get()` method with the id of the record you want to load. This will return a
single record. This id is the id from your own database, not the directus id. See get `getByCmsId` for information on
loading by directus id.

```php
$repository = new MyRepository();

$record = $repository->get(1);
```

#### getByCmsId()

To get a single record by the directus id, you can call the `getByCmsId()` method with the id of the record you want to
load. This will return a
single record. The id provide is the one from directus, not your own database id. See get id for more information.

```php
$repository = new MyRepository();

$record = $repository->getByCmsId(1);
```

### Advanced Usage

In some scenarios, you may need to load data from multiple collections or perform some preprocessing on the data before
it is saved to the database. This section explains how to customize the data handling process by overriding specific
methods in your repository.

#### Preparing Data

The `prepareData()` method is called before data is saved to the database. This method provides an opportunity to
manipulate the data array and should return a Laravel `Collection` containing the prepared data.

For instance, when loading a `Project` model along with associated `Skills`, you may need to process the skills and
append them to the main data array:

```php
public function getContext(): Context {
    return ContextFactory::create(Project::class, [
        'id',
        'hero',
        'name',
        'image',
        'status',
        'description',
        'short_description',
        'content',
        'url',
        'skills.*',
    ]);
}

protected function prepareData(array $data): Collection {
    $item = collect($data);
    $item->put('cms_id', $item->get('id'));

    $skills = array_map(static function ($skill) {
        return [
            'id' => $skill['skills_id'],
            'sort' => $skill['sort'],
        ];
    }, $item->get('skills'));

    $item->put('skills', $skills);
    return $item;
}
```

In the above example, `skills` data is processed and structured to be compatible with the `Project` model before being
saved.

#### Model Creation or Update

The `updateOrCreate()` method is used to create or update a model instance based on the provided data. This method can
be overridden to include custom logic for handling complex relationships or additional fields that are not directly part
of the main model.

Here's how you can handle complex data relationships, like attaching skills to a project:

```php
public function updateOrCreate(Collection $item): Model {
    /** @var Project $project */
    $project = $this->getContext()->getModelClass()::updateOrCreate(
        ['cms_id' => $item->get('cms_id')],
        $item->toArray()
    );

    // Assuming 'skills' data is provided as an array of skill IDs and sort values
    $skillCmsIds = array_map(static fn($skill) => $skill['id'], $item->get('skills'));
    $skillIds = $this->skillRepository->getSkillIds($skillCmsIds);

    $project->skills()->detach(); // Detach existing skills to avoid duplicates
    foreach ($item->get('skills') as $skill) {
        $project->skills()->attach($skillIds[$skill['id']], ['sort' => $skill['sort']]);
    }

    return $project;
}
```

In this example, after updating or creating the `Project` model, associated `Skills` are processed and attached using
the `attach` method of Laravel's Eloquent relationships. This ensures that the project's skills are updated in sync with
changes from Directus.

These advanced methods offer flexibility and robustness in your repository, allowing for complex data interactions that
are common in real-world applications.