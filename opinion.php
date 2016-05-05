<?php

  $pageContents = array(
    
    "paramsWithout"     =>  array("opt","graph", "sentiment"),
    
    "tabs"              =>  array(
    
      array(
        "id"            =>  "graphics",
        "name"          =>  "Temporal evolution",
        "rendererUri"   =>  "opinion_temporal.php",
        "filters"       =>  array("temporal","category","category_type","active"),
        "dependencies"  =>  array(
          "scripts"     =>  array(
            array(
              "library" =>  "amcharts",
              "version" =>  "3.19.2",
              "files"   =>  array("amcharts.js","themes/light.js","serial.js","amstock.js")
            )
          )
        )
      ),
      
      array(
        "id"            =>  "geographic",
        "name"          =>  "Geographic distribution",
        "rendererUri"   =>  "opinion_geographic.php",
        "filters"       =>  array("temporal","category","search_id","active"),
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
      ),
      
      array(
        "id"          =>  "data",
        "name"        =>  "Quantitative Data",
        "rendererUri" =>  "opinion_data.php",
        "filters"     =>  array("temporal","category","active"),
        "dependencies"  =>  array(
          "scripts"     =>  array(
            array(
              "library" =>  "amcharts",
              "version" =>  "3.19.2",
              "files"   =>  array("amcharts.js","pie.js","themes/light.js")
            )
          )
        )
      )

    )

  );

  return renderHTMLTabs($pageContents);
  
?>