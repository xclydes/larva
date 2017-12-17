# larva

A ORM to form implementation designed for laravel.

In order to include this package using composer, you will need to do the following:-

1. Modify you composer.json file to include:-
	```
	"repositories": [
        {
        	"type":"vcs",
            "url": "https://github.com/xclydes/larva"
        }
    ]
	```
	
2. Add a require (or require-dev) property:
	> "xclydes/larva" : "dev-master"
	
Once complete you can run `composer update` to have the package imported.

Add a custom provider in the app config 

        Xclydes\Larva\LarvaServiceProvider::class,

After import run `vendor:publish` to have the resources deployed.