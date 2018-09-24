Wordpress Asset
---------------

This is wordpress asset register or enqueue library

## Installation
Fetch this package via composer

```
$ composer require dawn/wordpress_asset
```

## Usage

If you only want to register a single resource, you can use `Script` class or `Style` class. The methods and property in `Script` class and `Style` class are basically the same, except `Script` class have async and defer property.

```php
Script::add('foo', array(
	'ver' => '1.0.0',
	'deps' => array('jquery'),
	'path' => '/fixtures/foo.js',
	'base' => '/public',
	'footer' => 'true',
	'area' => 'front',
	'async' => true,
  ));
```

If you have many asset to register, you can use `Package` Class.

```php
Package::add('foo', array(
	'script' => array(
		'bar' => array(
			'path' => '/fixtures/bar.js',
			'ver' => '1.2.0',
		),
		'foo' => array(
			'path' => '/fixtures/foo.js',
			'area' => 'admin',
		),
	),
	'style' => array(
		'bar' => array(
			'path' => '/fixtures/bar.css',
		),
		'foo' => array(
			'path' => '/fixtures/foo.css',
		),
	),
	'version' => '1.0.0',
	'base' => '/public',
));

```

## Asset Location

you can register asset to front or admin and others, Below is the area where you can register.

| area         | hook                       |
|:------------:|:--------------------------:|
| front        | wp_enqueue_scripts         |
| admin        | admin_enqueue_scripts      |
| login        | login_enqueue_scripts      |
| block_editor | enqueue_block_editor_asset |
| block        | enqueue_block_assets       |

## All functions 
| function                                     | description                                | return                 |
|----------------------------------------------|--------------------------------------------|------------------------|
| Script::add/Style::add/Package::add          | register script/style                      | return instance object |
| Script::queue/Style::queue/Package::queue    | enqueue script/style                       | return instance object |
| Script::remove/Style::remove/Package::remove | remove enqueued or registered script/style | return void            |
| Script::load/Style::load                     | load wordpress system asset                | return instance object |
| Script::get/Style::get/Package::get          | get registered script/style                | return instance object |
| Script::has/Style::has/Package::has          | has script/style instance                  | return boolean         |
| $style/$script->inline()                     | inline style/script                        | return $this           |
| $style/$script->dependences()                | the register asset dependences             | retrun $this           |
| $script->in()                                | the inline script position                 | return $this           |
| $style/$script->area()                       | the asset register location                | return $this           |
| $style/$script->base()                       | the asset base path                        | return $this           |
| $style/$script->path()                       | the asset path                             | return $this           |
| $style->media()                              | the style media                            | return $this           |
| $style->attribute()                          | attribute style                            | return $this           |
| $script->footer()                            | if register script in footer               | return $this           |
| $script->localize()                          | localize script                            | return $this           |
| $script->defer()                             | defer script                               | return $this           |
| $script->async()                             | async script                               | return $this           |

## License
MIT License

Copyright (c) 2018 dawn

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.