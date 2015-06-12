
<script type="text/javascript" src="<?php echo OCP\Util::linkToRoute('getapikey');?>"></script> 

<div id="app">
<div id="app-navigation">                                              
    <div id="geomap_position">                                         
            <h1> My Positions </h1>                                    
            <form method=GET action="#" name="form" id="geomap_date_form"></form>       
    </div>  
    <div id="geomap_gpx">                                              
            <h1> GPX Files </h1>                                       
        <div id="geomap_gpx_list">                                     
<?php       
        echo('<p>'.$l->t('Waiting for gpx data from your owncloud...').'</p>');                         
?>
        </div>
    </div>                                                             
    <div id="geomap_gpx_info">                                         
        <h1> GPX Information </h1>                                     
            <div id="data_view" class="data">  </div>                  
    </div>
</div>  
            
<div id="app-content">                                                 
        <div id="map_all">                        
                <div id="map_view" class="map" > </div>                
                <div id="plot_view" class="graph">  </div>             
                <div id="plot_view_speed" class="graphSpeed">  </div>  
        </div>  
</div>          
</div>
