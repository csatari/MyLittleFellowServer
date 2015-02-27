<?php
//$string = 
//$xmlObject = simplexml_load_string($string);

//print_r($xmlObject);

/*$result = $xmlObject->xpath('/osm/node');

while(list( , $node) = each($result)) {
    echo '/relation: ',$node,"\n";
}*/

/*$nodes = $xmlObject->node[0];
$east = 19.153209328651428;
$north = 47.74068701498578;
$south = 47.738551329993975;
$west = 19.14918601512909;
$goodNode = array();
$allNode = array();
foreach ($xmlObject->children() as $child) {
	if($child->getName() == 'node') {
		array_push($allNode,$child);
		if($child['lat'] < $north && $child['lat'] > $south && $child['lon'] < $east && $child['lon'] > $west) { //csak azok a pontok kerülnek be, amik a négyzetben benne vannak
			array_push($goodNode,$child);
		}
	}
}
print_r($goodNode);
echo "<br><br>";*/
//echo count($nodes->children('node'));

class OpenStreetMap {
	private $lat;
	private $long;
	private $tileSize;
	
	function __construct($latitude,$longitude,$tileSize) {
		$this->lat = $latitude;
		$this->long = $longitude;
		$this->tileSize = $tileSize;
        ini_set('max_execution_time', 300);
	}
	function getEast() {
		return $this->long+$this->tileSize;
	}
	function getNorth() {
		return $this->lat+$this->tileSize;
	}
	function getWest() {
		return $this->long-$this->tileSize;
	}
	function getSouth() {
		return $this->lat-$this->tileSize;
	}
	function getBBox() {
		return '<bbox-query e="'.$this->getEast().'" n="'.$this->getNorth().'" s="'.$this->getSouth().'" w="'.$this->getWest().'"/>';
	}

    /**
     * Lekéri az összes vizet a beállított koordináták alapján
     * @return string
     */
    function getWaterXML() {
		$url = 'http://overpass.osm.rambler.ru/cgi/interpreter';
		$data = array('data' => '<osm-script timeout="900" element-limit="1073741824">
		  <union>
			<query type="way">
			  <has-kv k="type" v="water"/>
			  '.$this->getBBox().'
			</query>
			<query type="way">
			  <has-kv k="waterway" v="river"/>
			  '.$this->getBBox().'
			</query>
			<query type="relation">
			  <has-kv k="natural" v="water"/>
			  '.$this->getBBox().'
			</query>
			<query type="way">
			  <has-kv k="natural" v="water"/>
			  '.$this->getBBox().'
			</query>
		  </union>
		  <union>
			<item/>
			<recurse type="down"/>
		  </union>
		  <print/>
		</osm-script>
		');

        print_r(htmlspecialchars($data['data']));
        echo "<br><br><br>";
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data),
                'timeout' => 1200,

			),
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		return $result;
	}

    /**
     * Lekérdezi az összes folyót a beállított koordináták alapján
     * @return string
     */
    function getRiverXML() {
		$url = 'http://overpass.osm.rambler.ru/cgi/interpreter';
		$data = array('data' => '<osm-script timeout="900" element-limit="1073741824">
		  <union>
			<query type="way">
			  <has-kv k="waterway" v="river"/>
			  '.$this->getBBox().'
			</query>
			<query type="node">
			  <has-kv k="type" v="river"/>
			  '.$this->getBBox().'
			</query>
		  </union>
		  <union>
			<item/>
			<recurse type="down"/>
		  </union>
		  <print/>
		</osm-script>
		');


		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data),
                'timeout' => 1200,
			),
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		return $result;
	}

    /**
     * Lekérdezi az összes látványosságot a koordináták alapján
     * @return string
     */
    public function getIntelligencePointsXML() {
        $url = 'http://overpass.osm.rambler.ru/cgi/interpreter';
        $data = array('data' => '<osm-script timeout="900" element-limit="1073741824">
                <union>
    <query type="way">
      <has-kv k="building" v="church"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="building" v="church"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="building" v="cathedral"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="building" v="cathedral"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="archaeological_site"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="archaeological_site"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="aircraft"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="aircraft"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="castle"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="castle"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="cannon"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="cannon"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="city_gate"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="city_gate"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="memorial"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="memorial"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="monument"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="monument"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="ruins"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="ruins"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="rune_stone"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="rune_stone"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="tree_shrine"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="tree_shrine"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="wayside_cross"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="wayside_cross"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="historic" v="wayside_shrine"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="historic" v="wayside_shrine"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="tourism" v="attraction"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="tourism" v="attraction"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="tourism" v="artwork"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="tourism" v="artwork"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="tourism" v="museum"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="tourism" v="museum"/>
      '.$this->getBBox().'
    </query>
    <query type="way">
      <has-kv k="tourism" v="viewpoint"/>
      '.$this->getBBox().'
    </query>
    <query type="node">
      <has-kv k="tourism" v="viewpoint"/>
      '.$this->getBBox().'
    </query>

  </union>
  <union>
    <item/>
    <recurse type="down"/>
  </union>
  <print/>
</osm-script>');
        print_r(htmlspecialchars($data['data']));
        echo "<br><br><br>";
        //echo htmlspecialchars($data['data']).'<br><br>';
        //echo '<br><br>';
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 1200,
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
	/*IPO-k
	<osm-script>
  <union>
    <query type="way">
      <has-kv k="building" v="church"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="building" v="church"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="building" v="cathedral"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="building" v="cathedral"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="archaeological_site"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="archaeological_site"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="aircraft"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="aircraft"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="castle"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="castle"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="cannon"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="cannon"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="city_gate"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="city_gate"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="memorial"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="memorial"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="monument"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="monument"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="ruins"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="ruins"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="rune_stone"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="rune_stone"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="tree_shrine"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="tree_shrine"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="wayside_cross"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="wayside_cross"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="historic" v="wayside_shrine"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="historic" v="wayside_shrine"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="tourism" v="attraction"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="tourism" v="attraction"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="tourism" v="artwork"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="tourism" v="artwork"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="tourism" v="museum"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="tourism" v="museum"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="way">
      <has-kv k="tourism" v="viewpoint"/>
      <bbox-query {{bbox}}/>
    </query>
    <query type="node">
      <has-kv k="tourism" v="viewpoint"/>
      <bbox-query {{bbox}}/>
    </query>
    
  </union>
  <union>
    <item/>
    <recurse type="down"/>
  </union>
  <print/>
</osm-script>
*/
}
?>