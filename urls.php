<?php

	$pageContents = array(
		
		"paramsWithout"		=>	array("opt","graph", "sentiment"),
		
		"tabs"				=>	array(
		
			array(
				"id"			=>	"frequency",
				"name"			=>	"Frecuencia",
				"rendererUri"	=>	"urls_frequency.php",
				"filters"		=>	array("date","category","search_id","active"),
				"dependencies"  =>  array(
				  "scripts"     =>  array(
					array(
					  "library" =>  "amcharts",
					  "version" =>  "3.19.2",
					  "files"   =>  array("amcharts.js","serial.js","themes/light.js")
					)
				  )
				)
			),
			
			array(
				"id"			=>	"cloud",
				"name"			=>	"Word Cloud",
				"rendererUri"	=>	"urls_word_cloud.php",
				"filters"		=>	array("date","category","search_id","active"),
				"dependencies"  =>  array(
				  "scripts"     =>  array(
					array(
					  "library" =>  "d3",
					  "version" =>  "3.5.12",
					  "files"   =>  array("d3.min.js","layouts/d3.layout.cloud.js")
					)
				  )
				)
			)

		)

	);

	return renderHTMLTabs($pageContents);
	
?>