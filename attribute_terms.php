<?php

//Create attribute from csv file

include_once('../wp-load.php');

function processCsv($absolutePath)
{
    $csv = array_map('str_getcsv', file($absolutePath));
    $headers = $csv[0];
    unset($csv[0]);
    $rowsWithKeys = [];
    foreach ($csv as $row) {
        $newRow = [];
        foreach ($headers as $k => $key) {
            $newRow[$key] = $row[$k];
        }
        $rowsWithKeys[] = $newRow;
    }
    return $rowsWithKeys;
}


//Read csv and return texonomy n term

$csv = processCsv('scents.csv');	//array_map('str_getcsv', file('perfumes.csv'));

$mainArr = array();

foreach($csv as $key => $csv_) {

	foreach($csv_ as $key => $c) {

		if(!empty($c)) {
			$mainArr[$key][] = $c;

			//$texonomy = strtolower($key);

			//wp_insert_term( $c, 'pa_'.$texonomy );
			

			//insert_product_attributes($postId = 18867, $key, $c);

		}

	}

}

// echo '<pre>'; print_r($mainArr); echo '</pre>'; die;
foreach($mainArr as $key => $mA) {

	//echo '<pre>'; print_r($mA); echo '<pre>'; die;

	foreach($mA as $ma) {

		$texonomy = strtolower(str_replace(' ', '-', $key));	//strtolower(trim($key));

		wp_insert_term( $ma, 'pa_'.$texonomy );


	}



}




?>