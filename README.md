Hilo
====

Hierarchical Loader.

## Installation

```
composer require shen2/hilo
```

## Usage

```php

class PromotionAdapter extends \Hilo\CustomAdapter {
	use \Hilo\JsonSerializer;
	
	protected $_cacheNamespace = 'promotion';
	protected $_ttl = 3600;
	
	public function fetch($key) {
		$ads = \Ad::select()
			->where('find_in_set(?, positions)', $key)
			->order('ad_id desc')
			->fetchAll();
		
		return $ads;
	}
}


$redisConfig = [...]; 
$redisManager = new RedisManager($redisConfig);

$adapter = new PromotionAdapter($redisManager);
 
$container = new Hilo\Container($adapter);

$container->get('key');
$container->getMulti(['key1', 'key2']);
```