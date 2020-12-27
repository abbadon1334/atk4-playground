<?php

declare(strict_types=1);

use Atk4\Data\Model;
use Atk4\Data\Persistence;
use Atk4\Data\Persistence\Array_;
use Atk4\Ui\App;
use Atk4\Ui\jsToast;
use Atk4\Ui\Layout\Generic;
use Atk4\Ui\Menu;
use Atk4\Ui\Message;
use Atk4\Ui\Table;

require dirname(__DIR__).'/../vendor/autoload.php';

$file = 'file.xml';

$dsn = 'sqlite::memory:';

// define model : https://agile-data.readthedocs.io/en/develop/

class Book extends Model
{
    public $table = 'books';

    protected function init(): void
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
    $bookModel = clone $model; // unload model id + data
    foreach ($element as $key => $val) {
        $bookModel->set($key, (string) $val); // set data fields
    }
    $bookModel->save(); // save the model to persistence
}

$app = new App(['title' => 'test xml']); // create App with a title
$app->initLayout([\Atk4\Ui\Layout::class]); // initialize layout

$menu = Menu::addTo($app); // add a FomanticUI menu to app
$menu->addClass('inverted'); // add a CSS class to menu
$menu->addItem('Create Table in DB') // add menu item
    ->on('click', function ($v) use ($dsn) { // create callback
        // this part of the code will be called only on click of the button

        $persistence = Persistence::connect($dsn); // connect to db

        $msg = Message::addTo($v, ['Migration']); // create a window message
        $msg->text = Migration::getMigration(new Book($persistence))->migrate(); // create Table

        return $msg; // return the message to update the user
    });

$menu->addItem('Populate DB Table')
    ->on('click', function ($v) use ($dsn, $model) {
        // this part of the code will be called only on click of the button

        $persistence = Persistence::connect($dsn); // connect to db
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
