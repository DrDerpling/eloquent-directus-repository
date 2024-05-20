# Directus Eloquent Repository

This package provides a repository pattern for that allows you to lazy load data from directus using eloquent models.

## requirements

- PHP 8.1 or higher
- Laravel 11 or higher
- Directus 10 or higher

## Installation

```bash
composer require drderpling/eloquent-directus-repository
```

## Usage

### Create a new repository

To create a new repository, create a new class that extends `EloquentDirectusRepository` and `getContext()` method that
returns the Context object. The Context object is used to define the model and the fields that should be loaded.

Here is a basic example of a repository that loads a model called `MyModel`:

```php
use DrDerpling\DirectusRepository\EloquentDirectusRepository;

class MyRepository extends EloquentDirectusRepository
{
      public function getContext(): Context
    {
        return ContextFactory::create(MyModel::class);
    }
}
```

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

- `$modelClass` - This is the class of the model that should be loaded. This is a required parameter.
- `$fields` - This is an array of fields that should be loaded. If this is empty, all fields will be loaded. This is an
  optional parameter.
- `$collectionName` - This is the name of the collection that should be loaded. If this is empty, the repository
  generate the collection name based on the model class. This is an optional parameter.
- `$orderBy` - This is the field that should be used to order the records. If this is empty, the repository will use the
  `sort` field. This is an optional parameter. Setting this to `null` will disable ordering.

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


## Advanced Usage


### Prepare Data
In some cases, you may want to load data from multiple collections our you want to do some processing on the data before
it is created. In these cases, you can override the `prepareData()` method. This method is called before the data is
saved to the database. This method should return a collection object.

In the example we are loading a project model but we also need the related skills. In the context we have defined the
fields that should be loaded. In the `prepareData()` method we are processing the skills and adding them to the data
before it is saved.

```php
        public function getContext(): Context
    {
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

    protected function prepareData(array $data): Collection
    {
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
### Model creation or update
In some cases, you may want to add additional logic when creating or updating a model. You can override the
`updateOrCreate()` method. This method is called when a model is created or updated. This method should return the model
object.

Like in the previous example we are loading a project model, but we also need the related skills. In the `updateOrCreate()`
method we are processing how we should attach the skills to the project.

```php
    public function updateOrCreate(Collection $item): Model
    {
        /** @var Project $project */
        $project = $this->getContext()->getModelClass()::updateOrCreate(
            ['cms_id' => $item->get('cms_id')],
            $item->toArray()
        );

        $skillCmsIds = array_map(static fn($skill) => $skill['id'], $item->get('skills'));
        $skillsIds = $this->skillRepository->getSkillIds($skillCmsIds);

        $project->skills()->detach();
        foreach ($item->get('skills') as $skill) {
            $project->skills()->attach($skillsIds[$skill['id']], ['sort' => $skill['sort']]);
        }

        return $project;
    }
```