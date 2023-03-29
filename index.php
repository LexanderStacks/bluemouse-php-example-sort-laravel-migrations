<?php 

/* --- PREPARE 1 --- CREATE BACKUP FOLDER IF NOT EXISTS --- */
	if( !is_dir("migrations-backup") ){
		mkdir("migrations-backup");
	}
/* --- ./ PREPARE 1 --- CREATE BACKUP FOLDER IF NOT EXISTS --- */

/* --- PREPARE 2 --- COPY ALL MIGRATION FILES INTO MIGRATIONS BACKUP FOLDER --- */
    $dir = opendir("migrations"); 
    @mkdir("migrations-backup"); 
    while(false !== ( $fileForBackup = readdir($dir)) ) { 
        if (( $fileForBackup != '.' ) && ( $fileForBackup != '..' )) { 
            if ( is_dir("migrations" . '/' . $fileForBackup) ) { 
                recurse_copy("migrations" . '/' . $fileForBackup,"migrations-backup" . '/' . $fileForBackup); 
            } 
            else { 
                copy("migrations" . '/' . $fileForBackup,"migrations-backup" . '/' . $fileForBackup); 
            } 
        } 
    } 
    closedir($dir); 
/* --- ./ PREPARE 2 --- COPY ALL MIGRATION FILES INTO MIGRATIONS BACKUP FOLDER --- */


/* --- PREPARE 3 --- DELETE ALL FILES FROM MIGRATIONS FOLDER --- */

	// Folder path to be flushed
	$folder_path = "migrations/";
	   
	// List of name of files inside
	// specified folder
	$filesToDelete = glob($folder_path.'/*'); 
	   
	// Deleting all the files in the list
	foreach($filesToDelete as $fileToDelete) {
	   
	    if( is_file($fileToDelete) ){
	        // Delete the given file
	        unlink($fileToDelete); 
	    }
	}
/* --- ./ PREPARE 3 --- DELETE ALL FILES FROM MIGRATIONS FOLDER --- */

/* --- PREPARE 4 ---- MAKE ARRAY OF ALL FILES FROM FOLDER TO SCAN --- */

	$folder = "migrations-backup/";
	// needed?
	$files = array();
	// Testing if this path is an achievable folder
	if( is_dir($folder) )
	{
	    // open folder
	    if( $handle = opendir($folder) )
	    {
	        // reading folder
	        while( ($fileToScan = readdir($handle) ) !== false)
	        {
	        	// file name containing condition
	        	if(str_contains($fileToScan, "php"))
	        	{
	        		// saving files into a php array
	        		$files[] = $fileToScan;
	        	}
	        }
	        closedir($handle);
	    }
	}

/* --- ./ PREPARE 4 MAKE ARRAY OF ALL FILES FROM FOLDER TO SCAN --- */

/* --- WRITE NEW FILES AND BACKUP FOLDER --- */

	// Declaring an array for all migration files of the folder
	$tables = array();

	foreach($files as $file)
	{
		/* --- READ ALL CONTENT LINES OF FILE AND PASS THEM INTO ONE ARRAY --- */
		$lines = array();

		$handle = fopen("migrations-backup/".$file, "r+");
		if($handle){
		    while (($buffer = fgets($handle, 4096)) !== false) {
		        $lines[] = $buffer;
		    }
		    if (!feof($handle)) {
		        echo "Error: unexpected fgets() fail\n";
		    }
			fclose($handle);
		}
		/* --- ./ READ CONTENT LINE BY LINE AND PASSING INTO AN ARRAY --- */

		/* --- PUT ALL NEEDED LINES FOR USE MODIFIED INSIDE NEW VALUES (ARRAYS) --- */
		// needed?
		$migrationRows = array();
		foreach($lines as $line){

			/* --- (1) GET THE TABLE NAME OF THE LINE INCLUDING TABLE NAME --*/
			if( str_contains($line, "Schema::create") ){
				$substring_start = (strpos($line, "create")) + 8;
				$substring_end = 
				( ( strpos($line, "function") - 2 ) - ($substring_start + 1) );
				// TABLE NAME DECLARATION
				$tableName = substr($line, $substring_start, $substring_end);
			}
			elseif( str_contains($line, "Schema::table") ){
				$substring_start = (strpos($line, "table")) + 7;
				$substring_end = 
				( ( strpos($line, "function") - 2 ) - ($substring_start + 1) );
				// TABLE NAME DECLARATION
				$tableName = substr($line, $substring_start, $substring_end);
			}
			/* --- ./ GET THE TABLE NAME OF THE LINE INCLUDING TABLE NAME --*/

			/* --- (2) GET ALL MIGRATION DEFINING ROWS OF EACH LINES INCLUDING THEM --*/
			if( str_contains($line, '$table->') ){
				if( !(str_contains($line, '$table->after')) && 
				    !(str_contains($line, '$table->dropColumn')) && 
				    !(str_contains($line, '$table->dropIfExists')) && 
				    !(str_contains($line, '$table->dropSoftDeletes')) 
				  ){
					if( str_contains($line, ';') ){
						// deleting ->after('...') substrings
						if( str_contains($line, "->after('") ){
							$is_nullable = false; 
							$is_defaultFalse = false; 
							$is_defaultTrue = false; 
							$is_default0 = false; 
							$is_default1 = false; 
							if( str_contains($line, "->nullable()") ){
								$is_nullable = true; 
							}
							if( str_contains($line, "->default(false)") ){
								$is_defaultFalse = true; 
							}
							if( str_contains($line, "->default(true)") ){
								$is_defaultTrue = true; 
							}
							if( str_contains($line, "->default(0)") ){
								$is_default0 = true; 
							}
							if( str_contains($line, "->default(1)") ){
								$is_default1 = true; 
							}

							// get first substring
							$substringFirstEnd = (strpos($line, "->after"));
							$subStringFirst = substr($line, 0, $substringFirstEnd);

							if($is_nullable){
								if( str_contains($subStringFirst, "->nullable()") ){
									$is_nullable = false; 
								}
							}
							if($is_defaultFalse){
								if( str_contains($subStringFirst, "->default(false)") ){
									$is_defaultFalse = false; 
								}
							}
							if($is_defaultTrue){
								if( str_contains($subStringFirst, "->default(true)") ){
									$is_defaultTrue = false; 
								}
							}
							if($is_default0){
								$default0 = "->default(0)";
							}
							else{
								$default0 = "";
							}
							if($is_default1){
								$default1 = "->default(1)";
							}
							else{
								$default1 = "";
							}

							// declare last substring
								if($is_nullable){
									if( !(str_contains($subStringFirst, "->nullable()")) ){
										$nullable = "->nullable()";
									}
								}
								else{
									$nullable = "";
								}
								if($is_defaultFalse){
									if( !(str_contains($subStringFirst, "->default(false)")) ){
										$defaultFalse = "->default(false)";
									}
								}
								else{
									$defaultFalse = "";
								}
								if($is_defaultTrue){
									if( !(str_contains($subStringFirst, "->default(true)")) ){
										$defaultTrue = "->default(true)";
									}
								}
								else{
									$defaultTrue = "";
								}
								if($is_default0){
									if( !(str_contains($subStringFirst, "->default(0)")) ){
										$default0 = "->default(0)";
									}
								}
								else{
									$default0 = "";
								}
								if($is_default1){
									if( !(str_contains($subStringFirst, "->default(1)")) ){
										$default1 = "->default(1)";
									}
								}
								else{
									$default1 = "";
								}
						
							// recreate the correct line ending defined as substringLast
							$subStringLast = $nullable.$defaultFalse.$defaultTrue.$default0.$default1.";";

							// get converted line without the "->after('...')" substring
							$line = $subStringFirst . $subStringLast;
						}
						$migrationRows[] = $line;
					}
					else{
						$migrationRows[] = "ERROR : Break was wrong";
					}
				}
			}
			/* --- ./ GET ALL MIGRATION DEFINING ROWS OF EACH LINES INCLUDING THEM --*/
		}
		/* --- ./ PUT ALL NEEDED LINES FOR USE MODIFIED INSIDE NEW VALUES (ARRAYS) --- */


		/* --- CREATE ARRAY OF ALL TABLES WITH TABLE NAMES AND MIGRATIONS (AS USEFUL MIGRATION LINES) --- */

		// if this file is a file representing a table
		if($tableName){
			// at first this file is not already inside the $tables[] array
			$is_inside = 0;
			// for each table from the second loop index on
			foreach ($tables as $table)
			{	
				/* if the tables array is containing the array element with the name parameter,
				   and if this is equal to the table var of the actual red file */ 
			    if($table['name'] == $tableName)
			    {
			    	/* this table element with the name parameter is already declared and inside the $table[] array */ 
		        	$is_inside = 1;
			    }
			}
			/* if there was no talbe element af all tables (from the $tables[] array) already inside with the parameter 'name' and the value of the above actual set $tableName array (insdie the files loop) */ 
			if($is_inside != 1){
				/* this is the first file which is read, so declare it's first parameters 'name' and 'migrations', with it's actual values from this actual loop index file */
				$tables[] = array('name' => $tableName, 'migrations' => $migrationRows);
			}else{
				/* this is not the first insert of an array with the two parameters and values above */
				foreach ($tables as $key => $table)
				{
					// if the tables array contains this actual red file already
				    if($table['name'] == $tableName)
				    {
				    	/* it is a second, or any next file, refering to the same migration table (a typical "add_table_column..." file) --- merge the migrations child array with the new single array $migrationRows[] into a new array variable */
			    		$mergedMigrations = array_merge($table['migrations'], $migrationRows);
						// remove the old table insert (child array) of the $tables[] array */
						unset($tables[$key]);
				    }
				}
				/* Add the new merged child array $table back into the parent array $tables[] */
				$tables[] = array('name' => $tableName, 'migrations' => $mergedMigrations);	
			}
		}
		/* --- ./ CREATE AN ARRAY OF ALL TABLES WITH TABLE NAMES AND MIGRATIONS --- */


		/* --- WRITE A NEW FILE ---*/

		// Sort tables by alphabet (by table names)
		sort($tables);

		// Go through all table elements
	    foreach($tables as $key => $table){

			// if this file is a file representing a table
			if($tableName){

		    	if( $table['name'] == $tableName ){
			    	// Create a following file for each table element
					$handle = fopen("migrations/".date("Y_m_d_His")."_create_".$table['name']."_table.php", "w");

						/* --- CONTENT OF THE NEW MIGRATION FILE --- */

						fwrite($handle, "<?php");
						fwrite($handle, "\n");
						fwrite($handle, "\n");
						fwrite($handle, "use Illuminate\Database\Migrations\Migration;");
						fwrite($handle, "\n");
						fwrite($handle, "use Illuminate\Database\Schema\Blueprint;");
						fwrite($handle, "\n");
						fwrite($handle, "use Illuminate\Support\Facades\Schema;");
						fwrite($handle, "\n");
						fwrite($handle, "\n");
						fwrite($handle, "class Create".str_replace('_', '', ucwords(($table['name']), '_'))."Table extends Migration");
						fwrite($handle, "\n");
						fwrite($handle, "{");
						fwrite($handle, "\n");
						fwrite($handle, "    /**");
						fwrite($handle, "\n");
						fwrite($handle, "     * Run the migrations.");
						fwrite($handle, "\n");
						fwrite($handle, "     *");
						fwrite($handle, "\n");
						fwrite($handle, "     * @return void");
						fwrite($handle, "\n");
						fwrite($handle, "     */");
						fwrite($handle, "\n");
						fwrite($handle, "    public function up()");
						fwrite($handle, "\n");
						fwrite($handle, "    {");
						fwrite($handle, "\n");
						fwrite($handle, "        Schema::create('".$table['name']."', function (Blueprint \$table ){");
						fwrite($handle, "\n");
						fwrite($handle, "\n");

							/* --- SORT AND WRITE BY ALPHABET ALL MIGRATION INSERT ROWS BY TYPE --- */
							// Sort all migration insert rows excetpt ID (FIRST ROW)
					    	$migrationId = null;
							// Sort all migration insert rows excetpt softDeletes (LAST -1 ROW)
					    	$migrationSoft = null;
							// Sort all migration insert rows excetpt softDeletes (LAST ROW)
					    	$migrationTimes = null;
					    	// Go through all migration rows
					    	foreach($table['migrations'] as $key => $migration){
					    		// 3 condition queries for the 3 exceptions
					        	if( str_contains($migration, "->id()") ){
					        		// put value into a new variable
									$migrationId = $table['migrations'][$key];
									// remove from array value, which should be sorted well by alphabet
									unset($table['migrations'][$key]);
					        	}
					        	elseif( str_contains($migration, "table->timestamps();") ){
					        		// ... same
									$migrationSoft = $table['migrations'][$key];
									// ... same
									unset($table['migrations'][$key]);
					        	}
					        	elseif( str_contains($migration, "table->softDeletes();") ){
					    			$migrationTimes = $table['migrations'][$key];
									unset($table['migrations'][$key]);
					        	}
					    	}
					    	// Sort the left array elements well by alphabet
							sort($table['migrations']);

							// write the migration row value at the first place
							fwrite($handle, $migrationId);

							// write all left migration row values of the parent array well sorted
					    	foreach($table['migrations'] as $key => $migration){
					    		if( isset($table['migrations'][$key]) && $table['migrations'][$key] != null )
					    		{
									fwrite($handle, $table['migrations'][$key]);
					    		}
					    	}
					    	// write the migration value softDeletes (LAST -1) at it`s place
					    	if( (isset($migrationSoft)) && ($migrationSoft != null ) ){
								fwrite($handle, $migrationSoft);
					    	}
					    	// write the last migration value at the end
					    	if( (isset($migrationTimes)) && ($migrationTimes != null ) ){
								fwrite($handle, $migrationTimes);
					    	}
							/* --- ./ SORT AND WRITE BY ALPHABET ALL MIGRATION INSERT ROWS BY TYPE --- */

						fwrite($handle, "\n");
						fwrite($handle, "        });");
						fwrite($handle, "\n");
						fwrite($handle, "    }");
						fwrite($handle, "\n");
						fwrite($handle, "\n");
						fwrite($handle, "    /**");
						fwrite($handle, "\n");
						fwrite($handle, "     * Reverse the migrations.");
						fwrite($handle, "\n");
						fwrite($handle, "     *");
						fwrite($handle, "\n");
						fwrite($handle, "     * @return void");
						fwrite($handle, "\n");
						fwrite($handle, "     */");
						fwrite($handle, "\n");
						fwrite($handle, "    public function down()");
						fwrite($handle, "\n");
						fwrite($handle, "    {");
						fwrite($handle, "\n");
						fwrite($handle, "        Schema::dropIfExists('".$table['name']."');");
						fwrite($handle, "\n");
						fwrite($handle, "    }");
						fwrite($handle, "\n");
						fwrite($handle, "}");
						fwrite($handle, "\n");


						/* --- ./ CONTENT OF THE NEW MIGRATION FILE --- */
					
					// close the new created and opened php file
					fclose($handle);
				}
			}

		}
		/* --- ./ WRITE A NEW FILE ---*/

	/* --- ./ WRITE NEW MIGRATION FILES --- */

	}

	echo "<br>Im Ordner \"migrations\" befinden sich nun zusammen gefasste und neu sortierte Migration-Dateien.";
		
?>