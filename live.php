<?php

  $pageContents = array(
    
    "paramsWithout"     =>  array("opt","graph"),
    
    "tabs"              =>  array(
    
      array(
        "id"            =>  "graphics",
        "name"          =>  "Temporal evolution (Live)",
        "rendererUri"   =>  "live_graph.php",
        "filters"       =>  array(),
        "dependencies"  =>  array(
          "scripts"     =>  array(
            array(
              "library" =>  "fusioncharts",
              "version" =>  "3.10.1",
              "files"   =>  array("fusioncharts.js","fusioncharts.charts.js","themes/fusioncharts.theme.fint.js")
            )
          )
        )
      )

    )

  );

  return renderHTMLTabs($pageContents);
  
?>
<script>
  $(window).trigger('content-loaded');
</script>