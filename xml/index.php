<?php

declare(strict_types=1);

use atk4\data\Model;
use atk4\data\Persistence;
use atk4\data\Persistence\Array_;
use atk4\schema\Migration;
use atk4\ui\App;
use atk4\ui\jsToast;
use atk4\ui\Layout\Generic;
use atk4\ui\Menu;
use atk4\ui\Message;
use atk4\ui\Table;

require dirname(__DIR__).'/vendor/autoload.php';

$file = 'file.xml';

$dsn = 'mysql://127.0.0.1:3306/test';
$usr = 'atk4_test';
$pwd = 'atk4_pass';

// define model : https://agile-data.readthedocs.io/en/develop/

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

$xml               = simplexml_load_file($file); // read XML
$ds                = [];
$array_persistence = new Array_($ds); // create model storage persistence
$model             = new Book($array_persistence); // create the mode with the persistence

foreach ($xml->book as $element) {
    $model->unload(); // unload model id + data
    foreach ($element as $key => $val) {
        $model->set($key, (string) $val); // set data fields
    }
    $model->save(); // save the model to persistence
}

$app = new App(['title' => 'test xml']); // create App with a title
$app->initLayout(Generic::class); // initialize layout

$menu = Menu::addTo($app); // add a FomanticUI menu to app
$menu->addClass('inverted'); // add a CSS class to menu
$menu->addItem('Create Table in DB') // add menu item
    ->on('click', function ($v) use ($dsn, $usr, $pwd) { // create callback
        // this part of the code will be called only on click of the button

        $persistence = Persistence::connect($dsn, $usr, $pwd); // connect to db

        $msg = Message::addTo($v, ['Migration']); // create a window message
        $msg->text = Migration::getMigration(new Book($persistence))->migrate(); // create Table

        return $msg; // return the message to update the user
    });

$menu->addItem('Populate DB Table')
    ->on('click', function ($v) use ($dsn, $usr, $pwd, $model) {
        // this part of the code will be called only on click of the button

        $persistence = Persistence::connect($dsn, $usr, $pwd); // connect to db
        $model_db = new Book($persistence); // use persistence DB for Model
        foreach ($model->getIterator() as $m) { // iterate the model with persistence array
            $model_db->tryLoad($m->id); // try to reload a previous inserted record
            $model_db->save($m); // save model data
        }

        return new jsToast('Successful Populate DB Table'); // return feedback to user
    });

$table = Table::addTo($app); // add FomanticUI table to App
$table->addClass('tiny'); // add a CSS class to Table
$table->setModel($model); // define the model to be show in the table as the model with array persistence
