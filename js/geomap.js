/******************************************************************************\
*	geoportail-shortcode.js
*	Project : Geoportail-Shortcode
*	Date    : 08/12
*	Auteur  : geoWP
*   comment : concatenation de Ajax.js / Chart.js / Form-Builder.js / Maps.js
\******************************************************************************/

var OCG_config                   = [];
OCG_config.vect_layers           = [];
OCG_config.pt_cur                = null;
OCG_config.layer_cur             = null;

/****************************************************************************************************************************\
*	owncloud.js
*	Project : owncloud
*	Date    : 11/12
*	Auteur  : geoWP
\*****************************************************************************************************************************/
if(!Array.prototype.last)
{
    Array.prototype.last = function()
    {
        return this[this.length - 1];
    };
}
/***/
function isArray(obj)
{
	if (typeof obj != "undefined")
		return obj.constructor == Array;	
	else
		return false;
}
$(document).ready(function(){
    var params = {
			"layers_array":[
				{"name":"GEOGRAPHICALGRIDSYSTEMS.MAPS:WMSC","opacity":"0.4","visibility":1}, 
				{"name":"ORTHOIMAGERY.ORTHOPHOTOS:WMSC","opacity":"1","visibility":1}
			],
			"gpx_array":[],
			"id":"view",
			"type":"normal",
			"lat":"47.4",
			"lon":"2.15",
			"z":"5",
			"territory":"FXX",
			"geolocate":"false",
			"graph":"true"
		};

    OCG_create_map(params);
    /*-------------------------------------------------------------------------
     * Actions for startup
     *-----------------------------------------------------------------------*/
    $('#geomap_position').hide();
    $('#geomap_gpx_info').hide();
    $.getJSON(
        OC.filePath('geomap', 'ajax', 'listDates.php'),
        {},
		function(jsondata)
		{
            var sel = $('<select>').change( function() {OCG_send_Points_To_Map(jsondata.uid , this.value);});
			$(jsondata.dates).each(function(i, date) {
                    sel.append($('<option>').attr('value',date).text(date));
                });
            $('#geomap_date_form').empty();
            $('#geomap_date_form').append(sel);
            $('#geomap_position').show();
		}
	);
    $.getJSON(
        OC.filePath('geomap', 'ajax', 'listFiles.php'),{},
		function(jsondata)
		{
            if(jsondata.length>0)
            {
                var table = $('<table></table>').addClass('mylist');
                jsondata.sort(function(a, b)
                {
                    str1 = a.name;
                    str2 = b.name;
                    return ( ( str1 == str2 ) ? 0 : (( str1 > str2 ) ? 1 : -1 ));  
                });
                $(jsondata).each(function(i, file) {
                        var row = $('<tr></tr>').addClass('bar').text(file.name).click(function() {OCG_get_gpx(file.url, file.name);});
                        table.append(row);
                    });
                $('#geomap_gpx_list').empty();
                $('#geomap_gpx_list').append(table);  
            }
            else
            {
                $('#geomap_gpx_list').empty();
                $('#geomap_gpx_list').append("<p> No GPX files are found in your ownCloud. Please upload a .gpx file. </p>");
            }
		}
	);
    $.getJSON(
        OC.filePath('geomap', 'ajax', 'listImages.php'),{},
        function(jsondata)
		{
            OCG_on_receive_images(jsondata);
		}
	);

});
/*----------------------------------------------------------------------------*\
* function OCG_Image_onFeatureSelect
\*----------------------------------------------------------------------------*/
function OCG_Image_onFeatureSelect(evt)
{
    var map = OCG_config.map;
    feature = evt.feature;
    //TODO utiliser preview owncloud -> cf apps/gallery
    popup = new OpenLayers.Popup.FramedCloud("featurePopup",
                             feature.geometry.getBounds().getCenterLonLat(),
                             new OpenLayers.Size(100,100),
                             "<h2>"+feature.attributes.name + "</h2>" +
                             "<a href='/index.php/apps/files/download/"+feature.attributes.url+"'> <img width='100' src='/index.php/apps/files/download/"+feature.attributes.url+"'></a>",
                             null, true);
    feature.popup = popup;
    popup.feature = feature;
    map.addPopup(popup);
}
/*----------------------------------------------------------------------------*\
* function oOCG_Image_onFeatureUnselect
\*----------------------------------------------------------------------------*/
function OCG_Image_onFeatureUnselect(evt)
{
    var map = OCG_config.map;
    feature = evt.feature;
    if (feature.popup) {
        popup.feature = null;
        map.removePopup(feature.popup);
        feature.popup.destroy();
        feature.popup = null;
    }
}
/*----------------------------------------------------------------------------*\
* function on_receive_images
\*----------------------------------------------------------------------------*/
function OCG_on_receive_images(jsondata)
{
    var map = OCG_config.map;
    var pointLayer = new OpenLayers.Layer.Vector("Image Layer");
    $(jsondata).each(function(i, file) {
                var point = new OpenLayers.Geometry.Point(file.lon, file.lat).transform( OCG_config.WGS84, OCG_config.projGeop);
                pointLayer.addFeatures([new OpenLayers.Feature.Vector(point,file)]); 
            });
    pointLayer.events.on({
            'featureselected': OCG_Image_onFeatureSelect,
            'featureunselected': OCG_Image_onFeatureUnselect
        });
    selectControl = new OpenLayers.Control.SelectFeature(pointLayer);
    map.addLayer(pointLayer);
    map.addControl(selectControl);
    selectControl.activate();
}
/*----------------------------------------------------------------------------*\
* function get_gpx
\*----------------------------------------------------------------------------*/
function OCG_get_gpx(gpx_url,gpx_name)
{
	//OC.dialogs.alert(t('Downloading gpx file ...'));
	var plot_graph=true;
	var gpx={'url': gpx_url,'name' : gpx_name};
	
	$.getJSON(
		OC.filePath('geomap', 'ajax', 'gpx.php'),
		{url: gpx.url},
		function(jsondata)
		{
			OCG_on_receive_gpx(jsondata,gpx,plot_graph);
		}
	);
}
/*----------------------------------------------------------------------------*\
* function on_receive_gpx
\*----------------------------------------------------------------------------*/
function OCG_on_receive_gpx(jsondata,gpx,plot_graph)
{
	if(jsondata.status == 'success')
	{
        
        if(plot_graph)
            OCG_clear_chart_data();
		OCG_clear_vect_layers();
		
        
		OCG_on_get_gpx_reponse(jsondata.data.gpxJSON,gpx,plot_graph);
		
        var map = OCG_config.map;
		var layer = OCG_config.vect_layers.last();
		map.zoomToExtent(layer.getDataExtent());
		
        var tmp = JSON.parse( jsondata.data.gpxJSON );
		var str = "<table class='mylist' >";
		str+= "<tr><td>Durée</td>   <td>"+tmp.info.timeTotal+"</td></tr>";
		str+= "<tr><td>Distance</td>  <td>"+Math.round(tmp.info.distTotal/100)/10+" km </td></tr>";
		str+= "<tr><td>D+</td>      <td>"+tmp.info.eleTotalPos+" m </td></tr>";
		str+= "<tr><td>D-</td>      <td>"+tmp.info.eleTotalNeg+" m </td></tr>";
		str+= "</table>";
        $('#geomap_gpx_info').show();
		$('#data_view').hide();
		$('#data_view').html(str);
		$('#data_view').fadeIn();
	}
	else
	{
		OC.dialogs.alert(jsondata.data.message, t('geomap', 'Error'));
	}
}
/*----------------------------------------------------------------------------*\
* function sendPointsToMap
\*----------------------------------------------------------------------------*/
function OCG_send_Points_To_Map(id_owner,date)
{
	$.getJSON(
		OC.filePath('geomap', 'ajax', 'position.php'),
		{id: id_owner, date: date},
		function(jsondata)
		{
			OCG_on_receive_Points_To_Map(jsondata,date);
		}
	);
}
/*----------------------------------------------------------------------------*\
* function sendPointsToMap
\*----------------------------------------------------------------------------*/
function OCG_on_receive_Points_To_Map(jsondata,date)
{
	if(jsondata.status == 'success')
	{
		OCG_clear_vect_layers();
        var data = JSON.parse( jsondata.data );
		var gpx=[];
		gpx.name= date;
		gpx.maps= "LINESTRING (" + data.line + ")";
		gpx.waypoints='';
		OCG_add_gpx(gpx,false);
        var map = OCG_config.map;
        var layer = OCG_config.vect_layers.last();
		map.zoomToExtent(layer.getDataExtent());
		//jQuery("#nb_pts").text('Nombre de points : ".$map['nb_pt']."');
	}
	else
	{
		OC.dialogs.alert(jsondata.data.message, t('geomap', 'Error'));
	}
}

/*----------------------------------------------------------------------------*\
* function onGetMapReponse
\*----------------------------------------------------------------------------*/
function OCG_on_get_gpx_reponse(gpxJSON,gpx,plot_graph)
{
    if(gpxJSON !== 0)
	{
		// Out : maps, graph, waypoints
        var gpxTemp = JSON.parse( gpxJSON );
		//var gpxTemp = eval('(' + gpxJSON + ')');
	
		gpx.maps = gpxTemp.maps;
		gpx.waypoints = gpxTemp.waypoints;

		OCG_add_gpx(gpx,plot_graph);
        //TODO
		if(plot_graph)
			OCG_add_data_to_chart(gpxTemp.graph,gpx.name);
	}
	else
		OC.dialogs.alert(jsondata.data.message, t('geomap', 'Error'));
}

/*----------------------------------------------------------------------------*\
* clear_vect_layers
/*----------------------------------------------------------------------------*/
function OCG_clear_vect_layers()
{
		var map = OCG_config.map;
		var tab = OCG_config.vect_layers;

		for (var i=0; i<tab.length; i++)
			map.removeLayer(tab[i]);

		OCG_config.vect_layers = [];
}
/*----------------------------------------------------------------------------*\
* function clear_chart_data
\*----------------------------------------------------------------------------*/
function OCG_clear_chart_data()
{

	cont = OCG_config.plot;
	if(cont !== null)
	{
		cont.data_chart = [];
		cont.data_chart_speed = [];
	}

}

/****************************************************************************************************************************\
*	Ajax.js
\*****************************************************************************************************************************/

/*----------------------------------------------------------------------------*\
* function get_gpx
\*----------------------------------------------------------------------------*/
//TODO à garder ?
/*
function WP_GEO_get_gpx(gpx,plot_graph)
{
    alert("WP_GEO_get_gpx");
	jQuery.post(
			ajaxurl,
			{'action':'get_gpx','url': gpx.url},
			function(response)
			{
				OCG_on_get_gpx_reponse(response,gpx,plot_graph);
			}
	);
}
*/


/****************************************************************************************************************************\
*	Chart.js
*	Project : Geoportail-Shortcode
*	Date : 10/05/12
*	Auteur : geoWP
/****************************************************************************************************************************/


/*----------------------------------------------------------------------------*\
* function myTicksFormaterX
\*----------------------------------------------------------------------------*/
function OCG_ticks_formaterX(val, axis) {
	if (val > 1000)
		return (val / 1000).toFixed(axis.tickDecimals) + " km";
	else
		return val.toFixed(axis.tickDecimals) + " m";
}
/*----------------------------------------------------------------------------*\
* function myTicksFormaterY
\*----------------------------------------------------------------------------*/
function OCG_ticks_formaterY(val, axis) {
	return val.toFixed(axis.tickDecimals) + " m";
}
/*----------------------------------------------------------------------------*\
* function myTicksFormaterYSpeed
\*----------------------------------------------------------------------------*/
function OCG_ticks_formaterYSpeed(val, axis) {
	return val.toFixed(axis.tickDecimals) + " km/h";
}
/*----------------------------------------------------------------------------*\
* function add_data_to_chart
\*----------------------------------------------------------------------------*/
function OCG_add_data_to_chart(data,name)
{

	cont = OCG_config.plot;
	// Les données a ploter
	//plot.data_chart = [];
	//plot.data_chart_speed = [];

	//var data_t = data.data;
	var data_temp = [];
	var data_speed_temp = [];

	for(j=0; j<data.dist.length; j++)
	{
		data_temp.push([
				data.dist[j],
				data.ele[j]
			]);
		data_speed_temp.push([
				data.dist[j],
				data.speed[j]
			]);
	}
	cont.data_chart.push(
		{
			data: data_temp,
			label: name
		}
	);
	cont.data_chart_speed.push(
		{
			data: data_speed_temp,
			label: name
		}
	);
	
	cont.plot.setData(cont.data_chart);
	cont.plot.setupGrid();
	cont.plot.draw();
	
	cont.plot_speed.setData(cont.data_chart_speed);
	cont.plot_speed.setupGrid();
	cont.plot_speed.draw();
}
/*----------------------------------------------------------------------------*\
* function create_chart
\*----------------------------------------------------------------------------*/
function OCG_create_chart()
{
	var cont = [];
	
	jQuery("#plot_view").show();
	jQuery("#plot_view_speed").show();

	// la div contenant le plot
	cont.placeholder = jQuery("#plot_view");
	cont.placeholder_speed = jQuery("#plot_view_speed");
	
	// les options du plot
	cont.options = {
		xaxis: { show: true, ticks: 5, tickFormatter: OCG_ticks_formaterX },
		yaxis: { show: true, ticks: 3, tickFormatter: OCG_ticks_formaterY },
		series: { lines: { show: true } },
		grid: { 
			hoverable: true,
			autoHighlight: true
		},
		crosshair: { mode: "x" },
		legend: { show: false}

	};
	// les options du plot speed
	cont.options_speed = {
		xaxis: { show: true, ticks: 5, tickFormatter: OCG_ticks_formaterX },
		yaxis: { show: true, ticks: 3, tickFormatter: OCG_ticks_formaterYSpeed },
		series: { lines: { show: true } },
		grid: { 
			hoverable: true,
			autoHighlight: true
		},
		crosshair: { mode: "x" },
		legend: { show: false}

	};

	// Les données a ploter
	cont.data_chart = [];
	cont.data_chart_speed = [];
	
	// Le plot	
	jQuery(function () {
		cont.plot = jQuery.plot(cont.placeholder, cont.data_chart, cont.options);
		cont.plot_speed = jQuery.plot(cont.placeholder_speed, cont.data_chart_speed, cont.options_speed);
        });
  // Fonction "hover"
  cont.placeholder.bind("plothover", function (event, pos, item) {
        OCG_on_plot_hover(event, pos, item,"");
    });
    // Fonction "hover"
  cont.placeholder_speed.bind("plothover", function (event, pos, item) {
        OCG_on_plot_hover(event, pos, item,"_speed");
    });
  
  OCG_config.plot = cont;

}
/*----------------------------------------------------------------------------*\
* function create_chart
\*----------------------------------------------------------------------------*/
/*
function OCG_create_chart(params)
{
    OCG_create_chart();
    
	if(isArray(params.data_array))
	{
		for(i=0; i<params.data_array.length; i++)
		{
			var data_t = params.data_array[i].data;
			OCG_add_data_to_chart(data_t,params.name);
		}
	}
}
*/
/*----------------------------------------------------------------------------*\
* function onPlotHover
\*----------------------------------------------------------------------------*/
function OCG_on_plot_hover(event, pos, item, type)
{
	var plot;
	
	if(type === "")
		plot = OCG_config.plot.plot;
	else
		plot = OCG_config.plot.plot_speed;

	var dataset = plot.getData();
	var d,z;
	if(dataset.length==1)
	{// s'il y a qu'une seule courbe :
	// on utilise l'intersection  du crosshair avec la courbe
		var axes = plot.getAxes();
    if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
        pos.y < axes.yaxis.min || pos.y > axes.yaxis.max)
        return;

    var series = dataset[0];

    // find the nearest points, x-wise
    for (j = 0; j < series.data.length; ++j)
        if (series.data[j][0] > pos.x)
            break;
    
    // now interpolate
    var p1 = series.data[j - 1], p2 = series.data[j];
    if (p1 === null)
        z = p2[1];
    else if (p2 === null)
        z = p1[1];
    else
        z = p1[1] + (p2[1] - p1[1]) * (pos.x - p1[0]) / (p2[0] - p1[0]);
		
	// on recalcul la position de la popup pour qu'elle suive la courbe
	var height = jQuery("#plot_view").height();
	var pageX = pos.pageX;
	var pageY = pos.pageY + (pos.y - z) * height / (axes.yaxis.max - axes.yaxis.min );
	
	// d et z sont tronqués
	d = (pos.x/1000).toFixed(0);
	z = z.toFixed(0); 

    // on affiche le popup et le point correspondant sur la carte 
    OCG_show_tooltip(pageX, pageY,z,d,type);
    OCG_display_point(j,series.label);
	}
	else
	{ // s'il y a plusieurs graph :
		if (item)//on réagit que si le curseur est sur la courbe
		{
			if (previousPoint != item.dataIndex)
			{
				previousPoint = item.dataIndex;

				d = (pos.x/1000).toFixed(0);
				z = pos.y.toFixed(0);
				
				// on affiche le popup et le point correspondant sur la carte 
				OCG_show_tooltip(item.pageX, item.pageY,z,d,type);
				OCG_display_point(item.dataIndex,item.series.label);
			}
		}
		else
		{
			jQuery("#tooltip").remove();
			previousPoint = null;            
		}
	}
}
/*----------------------------------------------------------------------------*\
* function showTooltip
\*----------------------------------------------------------------------------*/
function OCG_show_tooltip(x,y,z,d,type)
{		
	OCG_remove_tooltip();
	var contents;
	if(type ==="")
	{
		contents ="<table>"
        +"<tr><th style='font-weight:bold;text-align:right;width=50px'> Distance : </th>"
        +"<td style='text-align:right;width=50px'> &nbsp; "+d+" km </td> </tr>"
        +"<tr><th style='font-weight:bold;text-align:right;width=50px'> Altitude : </th>"
        +"<td style='text-align:right;width=50px'> &nbsp; "+z+"  m </td></tr>"
            +"</table>";
	} else
	{
		contents ="<table>"
                  +"<tr><th style='font-weight:bold;text-align:right;width=50px'> Distance : </th>"
                  +"<td style='text-align:right;width=50px'> &nbsp; "+d+" km </td> </tr>"
                  +"<tr><th style='font-weight:bold;text-align:right;width=50px'> Vitesse : </th>"
                  +"<td style='text-align:right;width=50px'> &nbsp; "+z+"  km/h </td></tr>"
                        +"</table>";
	}
		
	jQuery('<div id="tooltip">' + contents + '</div>').css( {
        position: 'absolute',
		display: 'none',
		'z-index': 10000,
		top: y + 5,
		left: x + 5,
		border: '1px solid #fdd',
		padding: '2px',
		'background-color': '#fee',
		opacity: 0.80
	}).appendTo("body").fadeIn(200);
	// jQuery("#tooltip").css('z-index')=1000;
}
/*----------------------------------------------------------------------------*\
* function removeTooltip
\*----------------------------------------------------------------------------*/
function OCG_remove_tooltip()
{
	jQuery("#tooltip").remove();
}
/*----------------------------------------------------------------------------*\
* function removeCorsshair
\*----------------------------------------------------------------------------*/
function OCG_remove_corsshair()
{
	OCG_config.plot.plot.clearCrosshair();
}
/*----------------------------------------------------------------------------*\
* function displayPointChart
* affiche une requete venant de openlayers
\*----------------------------------------------------------------------------*/
function OCG_display_point_chart(pt_id,layer_name,x,y)
{
	var plot = OCG_config.plot.plot;
	
	var dataset = plot.getData();

	var data;
	for( i in dataset)
	{
		if(dataset[i].label == layer_name)
			break;
	}
	data = dataset[i];
	var d_brute = data.data[pt_id][0];
	var d = (d_brute/1000).toFixed(0);
	var z = data.data[pt_id][1].toFixed(0);
	var position = jQuery("#map_view").offset();
    //var width = jQuery("#plot_"+viewer_id).width()
	// affiche Tooltip sur la carte
	OCG_show_tooltip(position.left+x+10,position.top+y+10,z,d,"");
	//
	//var axes = plot.getAxes();
	var pos =[];
	pos.x = d_brute;//((d_brute - axes.xaxis.min)*width/axes.xaxis.max).toFixed(0);
	pos.y = 50;

	plot.setCrosshair(pos);
}

/****************************************************************************************************************************\
*	Maps.js
/****************************************************************************************************************************/

/*----------------------------------------------------------------------------*\
* function create_map
* params :
*	id,type,lat,lon,z,territory,geolocate
*	layers_array,kml_array,gpx_array 
* TODO:
*	gpx_graph ?
*	wms ?
\*----------------------------------------------------------------------------*/
function OCG_create_map(params) {
    
    OCG_config.projGeop = new OpenLayers.Projection('EPSG:3857');
    OCG_config.WGS84 = new OpenLayers.Projection('EPSG:4326');

    //Create the map
    var map = new OpenLayers.Map({
            div: "map_view",
            projection: OCG_config.projGeop
        });    

    // Add some controls
    var scaleLine = new OpenLayers.Control.ScaleLine();
	scaleLine.maxWidth = 150;
	map.addControl(scaleLine);

	var layerswitcher = new OpenLayers.Control.LayerSwitcher(false);
	map.addControl(layerswitcher);

    var zoomControl = new OpenLayers.Control.ZoomBox();
    var zoomBar = new OpenLayers.Control.Zoom();
    map.addControl(zoomBar);

	map.addControl(new OpenLayers.Control.TouchNavigation());

    OCG_config.mouse_pos = new OpenLayers.Control.MousePosition();
	// Pour cacher l'affichage de la positon du curseur
	OCG_config.mouse_pos.formatOutput = function(e) {return '';};
	map.addControl(OCG_config.mouse_pos);

    OCG_config.map = map;
    
	if(params.layers_array !== undefined)
		OCG_add_layers(params.layers_array);

	if(params.geolocate != 'false')
		OCG_add_geolocate(params.z);

    else {
        //map center
        var position       = new OpenLayers.LonLat(parseFloat(params.lon),parseFloat(params.lat))
                                    .transform( OCG_config.WGS84, OCG_config.projGeop);
        var zoom           = parseInt(params.z); 
        map.setCenter(position, zoom );
    }
    
    if(params.graph != 'false')
        OCG_create_chart();

}

/*----------------------------------------------------------------------------*\
// Avanced Layers
/*----------------------------------------------------------------------------*/
//TODO n'ajotuer que les couches dans layers_array_in
function OCG_add_layers(layers_array_in)
{
    var map = OCG_config.map;
    
    //Add some layers :
    var mapnik = new OpenLayers.Layer.OSM("Mapnik");
    //var mapnik = new OpenLayers.Layer.OSM("Mapnik",TILES_URL+"?z=${z}&x=${x}&y=${y}&r=mapnik");
    map.addLayer(mapnik);
    
    //TODO ? GEOPORTAIL
    var options = {
        name: "Cartes IGN",
        //url: "http://wxs.ign.fr/"+API_KEY+"/geoportail/wmts",
        url:" http://gpp3-wxs.ign.fr/"+API_KEY+"/wmts",
        layer: "GEOGRAPHICALGRIDSYSTEMS.MAPS",
        matrixSet: "PM",
        style: "normal",
        numZoomLevels: 19,
        attribution: 'Map base: &copy;IGN <a href="http://www.geoportail.fr/" target="_blank"><img src="http://api.ign.fr/geoportail/api/js/2.0.0beta/theme/geoportal/img/logo_gp.gif"></a> <a href="http://www.geoportail.gouv.fr/depot/api/cgu/licAPI_CGUF.pdf" alt="TOS" title="TOS" target="_blank">Terms of Service</a>'
    };

    var cartes = new OpenLayers.Layer.WMTS(options);
    options.name = "Photos IGN";
    options.layer = "ORTHOIMAGERY.ORTHOPHOTOS";
    options.numZoomLevels = 20;
    var photos = new OpenLayers.Layer.WMTS(options);  
    map.addLayers([cartes, photos]);


    /*
	var layers_array;
	
	if(layers_array_in.length !== null)
		layers_array = layers_array_in;
	else {
        layers_array = JSON.parse( layers_array_in );
	}
	
	for(var i=(layers_array.length-1);i>=0;i--){
		var layer_options = {
				opacity:layers_array[i].opacity,
				visibility:layers_array[i].visibility
			};
			
		//Pour assurer la compatibilité avec les anciennes version de l'api.
		layers_array[i].name = layers_array[i].name.replace("WMSC","WMTS");
        
		viewer.addGeoportalLayer(layers_array[i].name,layer_options);
	}
    */
}
/*----------------------------------------------------------------------------*\
//kml_array
/*----------------------------------------------------------------------------*/
//TODO
/*
function WP_GEO_add_kml(kml_array_in)
{
	var map = OCG_config.map;

	var kml_array;

	if(kml_array_in.length !== null)
		kml_array = kml_array_in;
	else {
        // kml_array = eval(kml_array_in);
        kml_array = JSON.parse(kml_array_in);
	}
	
	for(var i=0;i<kml_array.length;i++)
	{
		map.addLayer(
			"KML",
			kml_array[i].name,
			kml_array[i].url,
			{
				visibility:true,
				minZoomLevel:1,
				maxZoomLevel:16
			}
		);
	}
}
*/
/*----------------------------------------------------------------------------*\
//gpx_array
/*----------------------------------------------------------------------------*/
//TODO
/*
function WP_GEO_add_gpx_array(gpx_array_in,plot_graph)
{
	var map = OCG_config.map;

	var gpx_array;

	if(gpx_array_in.length !== null)
		gpx_array = gpx_array_in;
	else{
		//gpx_array = eval(gpx_array_in);
        gpx_array = JSON.parse(gpx_array_in);
	}

	for(var i=0;i<gpx_array.length;i++)
	{
		OCG_add_gpx(gpx_array[i],plot_graph);
	}
}
*/
/*----------------------------------------------------------------------------*\
//gpx_array
/*----------------------------------------------------------------------------*/
//TODO
function OCG_add_gpx(gpx,plot_graph)
{
/*
	if(gpx.maps === null)
		OCG_get_gpx(gpx,id,plot_graph);
	else
	{
    */
	var map = OCG_config.map;

	var sourceproj= OCG_config.WGS84;
	var destproj= OCG_config.projGeop;

	var vect = new OpenLayers.Layer.Vector(
		gpx.name,
		{
			visibility:true,
			styleMap: new OpenLayers.StyleMap({
				pointRadius: 7,
				externalGraphic:"",
				graphicOpacity:1,
				strokeColor: "blue",
				strokeWidth: 3,
				strokeOpacity: 0.8,
				fillOpacity: 0.2,
				fillColor: "blue"
			})
		});
	//
	if(plot_graph)
	{
		vect.events.on(
			{
					'featureselected': OCG_onFeatureSelect,
					'featureunselected': OCG_onFeatureUnselect
			});

		control = new OpenLayers.Control.SelectFeature(vect,{hover: true});
		map.addControl(control);
		control.activate();
	}
	//
	if(gpx.waypoints !=="")
	{
		for(k=0; k<gpx.waypoints.length; k++)
		{
			var wp = gpx.waypoints[k];
	
			var point = new OpenLayers.Geometry.Point(wp.lon,wp.lat);
			point.transform(sourceproj, destproj);
			var f = new OpenLayers.Feature.Vector(point, wp);
			vect.addFeatures([f]);
		}
	}
	//
	var wkt_parser = new OpenLayers.Format.WKT();
	var features = wkt_parser.read(gpx.maps);
	if(features)
	{
		if(features.length !== undefined)// on a un tableau
		{
			for(var j=0;j<features.length;j++)
				features[j].geometry.transform(sourceproj, destproj);
			vect.addFeatures(features);
		}
		else // on a d'une feature
		{
			features.geometry.transform(sourceproj, destproj);
			vect.addFeatures([features]);
		}
	}

	OCG_config.vect_layers.push(vect);
	map.addLayers([vect]);
	//}
}

/*----------------------------------------------------------------------------*\
* geolocate
/*----------------------------------------------------------------------------*/
function OCG_add_geolocate(zoom)
{
	var map = OCG_config.map;
    
	var geolocate = new OpenLayers.Control.Geolocate({
		bind: true,
		geolocationOptions: {
			enableHighAccuracy: false,
			maximumAge: 0,
			timeout: 7000
		}
	});
	map.addControl(geolocate);
	var pulsate = function(feature){
			var point = feature.geometry.getCentroid(),
					bounds = feature.geometry.getBounds(),
					radius = Math.abs((bounds.right - bounds.left)/2),
					count = 0,
					grow = "up";
			var resize = function(){
                    if (count>16)
                        clearInterval(window.resizeInterval);
                    var interval = radius * 0.03;
                    var ratio = interval/radius;
                    switch(count)
                    {
                        case 4:
                        case 12:
                            grow = "down"; break;
                        case 8:
                            grow = "up"; break;
                    }
                    if (grow!=="up")
                        ratio = - Math.abs(ratio);
                    feature.geometry.resize(1+ratio, point);
                    vector.drawFeature(feature);
                    count++;
				};
			window.resizeInterval = window.setInterval(resize, 50, point, radius);
		};
	var vector = new OpenLayers.Layer.Vector("myLocation");
	map.addLayers([vector]);
	geolocate.events.register("locationupdated",geolocate,function(e)
		{
             //map center
            var position = new OpenLayers.LonLat(e.point.x,e.point.y)
                                    .transform( OCG_config.WGS84, OCG_config.projGeop);
         
            map.setCenter(position, zoom );

			vector.removeAllFeatures();
			var circle = new OpenLayers.Feature.Vector(
                OpenLayers.Geometry.Polygon.createRegularPolygon(
                    new OpenLayers.Geometry.Point(e.point.x, e.point.y),
                    e.position.coords.accuracy/2, 40, 0 ),
					{},
					{ fillColor: "#000", fillOpacity: 0.1, strokeWidth: 0 }
				);
			vector.addFeatures([
				      new OpenLayers.Feature.Vector(
				          e.point,
					  {},
				          {
				              graphicName: "cross",
				              strokeColor: "#f00",
				              strokeWidth: 2,
				              fillOpacity: 0,
				              pointRadius: 5
				          }
				      ),
				      circle
				  ]);
			pulsate(circle);
		});
	geolocate.activate();
}
/*----------------------------------------------------------------------------*\
* function onFeatureSelect
*	
\*----------------------------------------------------------------------------*/
function OCG_onFeatureSelect(e)
{
    var layer = e.feature.layer;
    OCG_config.layer_cur = layer;
    // on récupère la derniere position de la souris
    var xy=OCG_config.mouse_pos.lastXy;
    var lonlat = layer.getLonLatFromViewPortPx(xy);
    var point = new OpenLayers.Geometry.Point(lonlat.lon,lonlat.lat);
    // on recupere les points de la ligne survolee
    var line = e.feature.geometry.getVertices();
    // on cherche le point le plus proche
    var dMin=999999999999;
    var idMin=0;
    for(j=0; j<line.length; j++)
    {
        var pt_cur = line[j];
        var d_cur = pt_cur.distanceTo(point);
        if( d_cur < dMin)
        {
            idMin = j;
            dMin = d_cur;
        }
    }
    if(idMin !==0)// pour regler un petit bug
    {
        //on affiche le point
        OCG_display_point_layer(idMin,layer);
        OCG_display_point_chart(idMin,layer.name,xy.x,xy.y);
    }
}
/*----------------------------------------------------------------------------*\
* function onFeatureUnselect
*	
\*----------------------------------------------------------------------------*/
function OCG_onFeatureUnselect(e) 
{
	// on supprime le point apres un certain detail
	setTimeout(function() {
			OCG_remove_tooltip();
			OCG_remove_point();
			OCG_remove_corsshair();
        },1500);
}
/*----------------------------------------------------------------------------*\
* function displayPoint
* affiche un point courant le long de la trajecto des GPX
\*----------------------------------------------------------------------------*/
function OCG_display_point(pt_id,layer_name)
{
	//on recupere la map
	var map = OCG_config.map;
	var layers = map.getLayersByName(layer_name);
	if(layers.length !== 0)
	{
		var layer = layers[0];
		OCG_display_point_layer(pt_id,layer);
	}
}
/*----------------------------------------------------------------------------*\
* function displayPointLayer
* affiche un point courant le long de la trajecto des GPX
\*----------------------------------------------------------------------------*/
function OCG_display_point_layer(pt_id,layer)
{
	// Supprime ancien point
	OCG_remove_point();
	OCG_config.layer_cur = layer;
	
	var multilines;
	var fini = false;
	var id=0;
	while(!fini)
	{
         multilines = OCG_config.layer_cur.features[id].geometry.components;
         if(multilines !== undefined)
            fini = true;
         id++;
	}
	var i = 0;
	var vertices;
	fini = false;
	while(!fini && i < multilines.length)
	{
		vertices = multilines[i].getVertices();
		if(pt_id >= vertices.length)
		{
			i+=1;
			pt_id -= vertices.length;
		}else
		{
			fini = true;
		}
	}
	if(!fini)
		alert("pb multilignes");
	//multilinestring
	var point = vertices[pt_id];
	OCG_config.pt_cur = new OpenLayers.Feature.Vector(point);
	OCG_config.layer_cur.addFeatures([OCG_config.pt_cur]);
}
/*----------------------------------------------------------------------------*\
* function removePoint
\*----------------------------------------------------------------------------*/
function OCG_remove_point()
{
	// Supprime ancien point
	if((OCG_config.layer_cur !== null) && (OCG_config.pt_cur !== null) )
		OCG_config.layer_cur.removeFeatures([OCG_config.pt_cur]);
}
