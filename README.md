
Configuration
=============
You will want to run the following command to publish the config to your application, otherwise it will be overwritten when the package is updated.
```shell
php artisan vendor:publish --provider="AgelxNash\modLaravel\ServiceProvider"
```

Now you can edit the file `config/modx.php`

Usage
======
Create your snippet in the config file `config/modx.php`
```php
return array(
    'snippets' => array(
        'user' => function($params){
            $id = isset($params['id']) ? (int)$params['id'] : 0;
            $field = (isset($params['field']) && in_array($params['field'], array('name', 'email'))) ? (string)$params['field'] : 'name';
            $userObj = App\Models\User::findOrNew($id);
            return $userObj->{$field};
        }
    )
);

```
Now you can call a snippet `user`
```php
$text = 'Some data: [[example? &id=`asd`]]. User: [[user? &id=`2`]]';
return Modx::mergeSnippets($text);
```

As a result, you get something like this
```
Some data: Array ( [id] => asd ). User: Admin
```

Attention
======
Do not get carried away with this garbage. It is absolutely safe and can lead to cracking of your site.

See
======
 - [https://github.com/modxcms/evolution](https://github.com/modxcms/evolution)