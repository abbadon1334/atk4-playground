<?php

declare(strict_types=1);

use atk4\data\Model;
use atk4\data\Persistence;
use atk4\data\Persistence\Array_;
use atk4\schema\Migration;
use atk4\ui\App;
use atk4\ui\Layout\Admin;
use atk4\ui\Layout\Generic;
use atk4\ui\Menu;
use atk4\ui\Message;
use atk4\ui\Table;

require 'vendor/autoload.php';

$file = 'file.xml';

$dsn = 'mysql://127.0.0.1:3306/test';
$usr = 'user';
$pwd = 'pass';

class Book extends Model
{
    public $table = 'books';

    public function init(): void
    {
        parent::init();

        $this->addField('author');
        $this->addField('title');
        $this->addField('description');
        $this->addField('genre');
        $this->addField('price', ['type' => 'money']);
        $this->addField('publish_date', ['type' => 'date']);
    }
}

$xml = simplexml_load_file($file);
$ds = [];
$array_persistence = new Array_($ds);
$model = new Book($array_persistence);

foreach ($xml->book as $element) {
    $model->unload();
    foreach ($element as $key => $val) {
        $model->set($key, (string)$val);
    }
    $model->save();
}

$app = new App(['title' => 'test xml']);
$app->initLayout(Generic::class);

Menu::addTo($app)->addClass('inverted')->addItem('Create Table in DB')
    ->on('click', function ($v) use ($dsn, $usr, $pwd) {
        $persistence = Persistence::connect($dsn, $usr, $pwd);

        return new Message([
            'label' => 'Migration',
            'text'  => Migration::getMigration(new Book($persistence))->migrate(),
        ]);
    });

Table::addTo($app)->addClass('tiny')->setModel($model);