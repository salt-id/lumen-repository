
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

That's it ! Now you have a CRUD 
