<?php

use Polyfony\Response 	as Response;
use Polyfony\Cache 		as Cache;
use Polyfony\Config 	as Config;
use Polyfony\Database 	as Database;

class AliveController extends Polyfony\Controller {

	// database model
	const dummy_model_file 		= '../Private/Models/AliveDummy.php';
	const dummy_model_contents 	= '<?php namespace Models; class AliveDummy extends \Polyfony\Record {} ?>';

	// database
	const dummy_table_removal 	= 'DROP TABLE IF EXISTS "AliveDummy";';
	const dummy_table_creation 	= 'CREATE TABLE "AliveDummy" ( "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT, "random_value" text NOT NULL );';

	// filesystem
	const dummy_file_path		= '../Private/Storage/Data/DummyFile';
	// cache
	const dummy_cache_variable 	= 'AliveDummy'; 

	public function indexAction() {

		// the output is gonna be json
		Response::setType('json');
		// disable caching for this critical controller
		Response::disableOutputCache();
		// disable the profiler
		Config::set('profiler','enable', 0);

		
		// check the filesystem
		try { $this->assertFilesystemStatus(); } 
		catch (Exception $e) {
			Response::setStatus(500);
			Response::setContent(['error'=>$e->getMessage()]);
			Response::render();
		}

		// check the database
		try { $this->assertDatabaseStatus(); } 
		catch (Exception $e) {
			Response::setStatus(500);
			Response::setContent(['error'=>$e->getMessage()]);
			Response::render();
		}
		
		// check the cache 
		try { $this->assertCacheStatus(); } 
		catch (Exception $e) {
			Response::setStatus(500);
			Response::setContent(['error'=>$e->getMessage()]);
			Response::render();
		}
		
		// if we got all the way here, everything seems ok
		Response::setStatus(200);
		Response::setContent('OK');
		Response::render();

	}

	private function assertDatabaseStatus() :void {

		// if the dummy model does not exist
		if(!file_exists(self::dummy_model_file)) {
			// create a dummy model
			file_put_contents(
				self::dummy_model_file, 
				self::dummy_model_contents
			);
		}
		// create a dummy table
		Database::query()->query(self::dummy_table_removal)->execute();
		Database::query()->query(self::dummy_table_creation)->execute();
		// define a random value to insert
		$random_value = $this->getRandomValue();
		// insert something in that dummy table
		$dummy_object = new Models\AliveDummy;
		// set the value and save
		$dummy_object->set(['random_value'=>$random_value])->save();
		// if the dummy didn't get an id
		if(!$dummy_object->get('id')) {
			// failure, stop here
			Throw new Exception('Dummy object failed to get an ID from the database');
		}
		// try to read the dummy table
		$dummy_clone = new Models\AliveDummy($dummy_object->get('id'));
		// if the random value mismatches
		if($dummy_clone->get('random_value') != $random_value) {
			// failure, stop here
			Throw new Exception('Dummy object from the database has a missmatching random value');
		}
		// remove the database
		Database::query()->query(self::dummy_table_removal)->execute();
		// remove model
		unlink(self::dummy_model_file);

	}

	private function assertFilesystemStatus() :void {

		// get some random data
		$random_value = $this->getRandomValue();
		// create a dummy file 
		file_put_contents(self::dummy_file_path, $random_value);
		// compare the contents of the file with our random value
		if(file_get_contents(self::dummy_file_path) != $random_value) {
			// failure, stop here
			Throw new Exception('The dummy file contents has a missmatching random value');
		}
		// remove the file
		if(!unlink(self::dummy_file_path)) {
			Throw new Exception('Failed to remove the dummy file');
		}

	}

	private function assertCacheStatus() :void {

		// get a random value
		$random_value = $this->getRandomValue();
		// put the value in the cache
		Cache::put(self::dummy_cache_variable, $random_value, true);
		// if we have a missmatch in random values
		if(Cache::get(self::dummy_cache_variable) != $random_value) {
			// failure, stop here
			Throw new Exception('Dummy cache has a missmatching random value');
		}
		// remove from the cache
		Cache::remove(self::dummy_cache_variable);

	}

	private function getRandomValue() :string {

		return (string) sha1(microtime(true));

	}

}

?>
