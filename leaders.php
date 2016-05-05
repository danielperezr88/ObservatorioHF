<?php

	$pageContents = array(
		
		"paramsWithout"		=>	array("opt","graph", "sentiment"),
		
		"tabs"				=>	array(
		
			array(
				"id"			=>	"frequency",
				"name"			=>	"Frecuencia",
				"rendererUri"	=>	"leaders_frequency.php",
				"filters"		=>	array("temporal","category","search_id","active"),
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
				"rendererUri"	=>	"leaders_word_cloud.php",
				"filters"		=>	array("temporal","category","search_id","active"),
				"dependencies"  =>  array(
				  "scripts"     =>  array(
					array(
					  "library" =>  "d3",
					  "version" =>  "3.5.12",
					  "files"   =>  array("d3.min.js","layouts/d3.layout.cloud.js")
					)
				  )
				)
			),
			
			array(
				"id"			=>	"geographic",
				"name"			=>	"Geographic distribution",
				"rendererUri"	=>	"leaders_geographic.php",
				"filters"		=>	array("temporal","category","search_id","active"),
				"dependencies"  =>  array(
				  "scripts"     =>  array(
					array(
					  "library" =>  "openlayers",
					  "version" =>  "3.13.1",
					  "files"   =>  array("ol3.4.js")
					)
				  ),
				  "css"     =>  array(
					array(
					  "library" =>  "openlayers",
					  "version" =>  "3.13.1",
					  "files"   =>  array("ol.css")
					)
				  )
				)
			)

		)

	);

	return renderHTMLTabs($pageContents);
	
?>