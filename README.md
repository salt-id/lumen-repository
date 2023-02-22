
# Installation


### System Requirements

| Stack                     | Version   |
|:--------------------------|:----------|
| `PHP`                     | `^8.0`    |
| `laravel/lumen-framework` | `^9.0`    |
| `league/fractal`          | `^0.20.1` |


### Composer

This package is available on [Packagist](https://packagist.org/packages/saltid/lumen-repository) and can be installed using [Composer](https://getcomposer.org/):

```bash
$ composer require saltid/lumen-repository
```

# Interfaces

The `SaltId\LumenRepository\Repositories\AbstractRepository` implementing below interfaces

- [SaltId\LumenRepository\Contracts\RepositoryInterface](https://github.com/salt-id/lumen-repository/blob/master/src/Contracts/RepositoryInterface.php)
- [SaltId\LumenRepository\Contracts\RepositoryCriteriaInterface](https://github.com/salt-id/lumen-repository/blob/master/src/Contracts/RepositoryCriteriaInterface.php)

`SaltId\LumenRepository\Criteria\AbstractCriteria` is implementing

- [SaltId\LumenRepository\Contracts\CriteriaInterface](https://github.com/salt-id/lumen-repository/blob/master/src/Contracts/CriteriaInterface.php)


and `SaltId\LumenRepository\Criteria\RequestCriteria` is extending `SaltId\LumenRepository\Criteria\AbstractCriteria`

Criteria is useful for filtering your data, for example the default Criteria is `RequestCriteria` and has responsibility to handle search, orderBy, sortBy. You can create your own by extending the `AbstractCriteria` or implementing `CriteriaInterface` and push it to repository with method `pushCriteria(CriteriaInterface $criteria): static;`

# Usage

Your **Model** should implements **`\SaltId\LumenRepository\Contracts\TransformableInterface`** and implementing
**`transform(): array`** method

~~~ php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SaltId\LumenRepository\Contracts\TransformableInterface;

class Article extends Model implements TransformableInterface
{
    // your model content.

    public function transform(): array
    {
        // return your model toArray() as default
        return $this->toArray();

        // or you can transform it into anything you wanted.
        // for example :
        //return [
        //    'my_id' => $this->id,
        //    'my_title' => $this->title,
        //    'my_relation_to_other_model_id' => $this->getOtherModel()?->id,
        //    'my_relation_to_other_model_to_array' => $this->getOtherModel()?->toArray(),
        //    'my_relation_to_other_model_to_transform' => $this->getOtherModel()?->transform(),
        //];
    }
}

?>
~~~

Create **Transformer** class and extend it with **`SaltId\LumenRepository\Transformers\TransformerAbstract`** and it will look like :

~~~ php
<?php

namespace App\Transformers;

use SaltId\LumenRepository\Transformers\TransformerAbstract;

class ArticleTransformer extends TransformerAbstract
{

}
~~~

Create **Presenter** class and extend it with **`SaltId\LumenRepository\Presenter\FractalPresenter`** and override the abstact method **`getTransformer(): ?TransformerAbstract`** it will look like :

~~~ php
<?php

namespace App\Presenters;

use App\Transformers\ArticleTransformer;
use SaltId\LumenRepository\Presenter\FractalPresenter;
use SaltId\LumenRepository\Transformers\TransformerAbstract;

class ArticlePresenter extends FractalPresenter
{
    public function getTransformer(): ?TransformerAbstract
    {
        return app(ArticleTransformer::class);
    }
}

~~~

Extend your **Repository** class with **`SaltId\LumenRepository\Repositories\AbstractRepository`**


~~~ php
<?php

namespace App\Repositories;

use SaltId\LumenRepository\Repositories\AbstractRepository;
use SaltId\LumenRepository\Contracts\PresenterInterface;

class ArticleRepository extends AbstractRepository
{
    protected array $searchableFields = [
        'title',
        'description'
    ];
    
    public function presenter(): ?PresenterInterface
    {
        // return should be implement PresenterInterface
        return app(ArticlePresenter::class);
    }
}
~~~

Now, It's time to use our repository ! But first, this may not a good practice since we use repository directly from controller you can create a service or usecase class and inject the `RepositoryInterface`.

On your controller

~~~ php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use SaltId\LumenRepository\Contracts\RepositoryInterface;

class ArticleController extends Controller
{
    protected RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // Or you can add the line code above in \App\Http\Controllers\Controller
    // so you don't need the declare it everytime. It's up to you.

    public function index(): array
    {
        return $this->repository->all();

        //return $this->repository->paginate();
    }

    // you may create FormRequest for validation.
    public function store(Request $request): array
    {
        return $this->repository->create($request->all());
    }

    public function show(int $id): array
    {
        return $this->repository->find($id);
    }

    public function update(Request $request, int $id): array
    {
        return $this->repository->update($request->all(), $id);
    }

    public function destroy(int $id): array
    {
        return $this->repository->delete($id);
    }
}
~~~

On `routes/(web|api).php`

~~~ php
<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/article', 'ArticleController@index');
$router->post('/article', 'ArticleController@store');
$router->get('/article/{id}', 'ArticleController@show');
$router->put('/article/{id}', 'ArticleController@update');
$router->delete('/article/{id}', 'ArticleController@destroy');

~~~

Since we inject the `RepositoryInterface` in our controller, we will use `service provider` to tell the controller which repository we will use on our controller.

Create your service provider class called `RepositoryServiceProvider`

~~~ php
<?php

namespace App\Providers;

use App\Http\Controllers\ArticleController;
use App\Models\Article;
use App\Repositories\ArticleRepository;
use Illuminate\Support\ServiceProvider;
use SaltId\LumenRepository\Contracts\RepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(ArticleController::class)
        ->needs(RepositoryInterface::class)
        ->give(function() {
            return new ArticleRepository(new Article());
        });
    }
}

~~~

The code above, we tell the application when `ArticleController` is executed and needs `RepositoryInterface` it will give `ArticleRepository` with model `Article` to the controller. **Don't forget to register `RepositoryServiceProvider` in your `bootstrap/app.php`**

That's it ! Now you have a CRUD in defined endpoint at`routes/(web|api).php`
