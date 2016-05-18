<?php

	$pageContents = array(
		
		"paramsWithout"		=>	array("opt","graph", "sentiment"),
		
		"tabs"				=>	array(
		
			array(
				"id"			=>	"frequency",
				"name"			=>	"Frecuencia",
				"rendererUri"	=>	"research_graphic.php",
				"filters"		=>	array("temporal","category","search","active"),
				"dependencies"  =>  array(
				  "scripts"     =>  array(
					array(
					  "library" =>  "amcharts",
					  "version" =>  "3.19.2",
					  "files"   =>  array("amcharts.js","serial.js","themes/light.js")
					),
					array(
					  "library" =>  "tag-it",
					  "version" =>  "2.0",
					  "files"   =>  array("tag-it.js")
					)
				  ),
				  "css"     =>  array(
					array(
					  "library" =>  "tag-it",
					  "version" =>  "2.0",
					  "files"   =>  array("jquery.tagit.css")
					),
					array(
					  "library" =>  "daterangepicker",
					  "version" =>  "2.1.19",
					  "files"   =>  array("daterangepicker.css")
					)
				  )
				)
			)

		)

	);

	return renderHTMLTabs($pageContents);
	
?>
