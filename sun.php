<?php
	// calculates the sun position and path throughout the day
	// input params: LAT&LON
	// output json
	
	// not much commenting in code
	// ported from http://www.stjarnhimlen.se/comp/tutorial.html		
	// MANY THANKS!
	// most var-names are identical to above tutorial
	
	
//	$LAT = deg2rad($_GET["lat"]);
//	$LON = deg2rad($_GET["lon"]);

	$LAT = deg2rad(42.42);
	$LON = deg2rad(23.20);
	
	$year = gmdate("Y");
	$month = gmdate("m");
	$day = gmdate("d");
	$hour = gmdate("H") + (gmdate("i") / 60);

  // get current position
	getsunpos($LAT, $LON, $year, $month, $day, $hour, $azimuth, $altitude, $sunstate);


//Solar panel's tilt angle
	$modtilt=40;

//Solar panel's azimut
	$modazi=200;

//Solar panel's surface in sq. meters
	$modsufrace=1.88512;

 	//The intensity of the direct component of sunlight throughout each day can be determined as a function of air mass 
	//http://pveducation.org/pvcdrom/properties-of-sunlight/air-mass#formula      
	$airmass=1/cos((90-$altitude)*4*asin(1)/360); 

       //Sincident is the intensity on a plane perpendicular to the sun's rays in units of kW/m2 and AM is the air mass. The value of 1.353 kW/m2 is the solar constant and the number 0.7 arises from the fact that about 70% of the radiation incident on the atmosphere is transmitted to the Earth. The extra power term of 0.678 is an empirical fit to the observed data and takes into account the non-uniformities in the atmospheric layers.
	$Sincident=(1.353*pow(0.7,pow($airmass,0.678)));

	//A module that directly faces the sun so that the incoming rays are perpendicular to the module surface has the module tilt equal to the sun's zenith angle (90 - a = ß), and the module azimuth angle equal to the sun's azimuth angle 
	//Solar tubes are an example where the module azimuth can be treated as hooked to the solar's azimuth foe a -90/+90 degree angle
	//Comment out the following if you are on FLAT panel, not solar tubes:
	if($azimuth>($modazi-80) && $azimuth<($modazi+80)) {
		$modazi=$azimuth;
	}

       $fraction = cos($altitude*4*asin(1)/360)*sin($modtilt*4*asin(1)/360)*cos($azimuth*4*asin(1)/360-$modazi*4*asin(1)/360)+sin($altitude*4*asin(1)/360)*cos($modtilt*4*asin(1)/360);

	// kW/m² light intensity on the module * module's surface
	$Smodule = $Sincident * $fraction * $modsufrace * 1000;

	if($Smodule<0) $Smodule=0;

	if( $altitude < 0 ) {
		$altitude=0;
   	       $azimuth=0;
		$Smodule=0;
	}


	echo "{\"result\":\"OK\",\"azimuth\":$azimuth,\"altitude\":$altitude,\"smodule\":$Smodule}";



	$url = "http://harizanov.com/emon/api/post?apikey=9aad1b7d53ee3842742c07cfe1781ef4&json={\"azimuth\":$azimuth,\"altitude\":$altitude,\"smodule\":$Smodule}";

	//echo $url;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$contents = curl_exec ($ch);
	curl_close ($ch);

	die();



	function getsunpos($LAT, $LON, $year, $month, $day, $hour, &$azimuth, &$altitude, &$sunstate) {
	
	  // julian date
	$d = 367*$year - floor((7*($year + floor(($month+9)/12)))/4)
				 + floor((275*$month)/9) + $day - 730530;
		
	$w = 4.9382415669097640822661983551248 
			 + .00000082193663128794959930855831205818* $d; // (longitude of perihelion)
	  $a = 1.000000                           ;//    (mean distance, a.u.)
	  $e = 0.016709 - .000000001151        * $d ;//   (eccentricity)
	  $M = 6.2141924418482506287494932704807 
	  	 + 0.017201969619332228715501852561964 * $d ;//   (mean anomaly)
		
		
	$oblecl = 0.40909295936270689252387465029835 
						- .0000000062186081248557962825791102081249 * $d  ;// obliquity of the ecliptic
		
	$L = $w + $M; // sun's mean longitude
		
	$E = $M + $e * sin($M) * (1 + $e * cos($M));
		
	$x = cos($E) - $e;
        $y = sin($E) * sqrt(1 - $e * $e);
	    
	  $r = sqrt($x*$x + $y*$y);
	  $v = atan2( $y, $x )  ;
	    
	  $lon = $v + $w;
	  
	  $x = $r * cos($lon);
	  $y = $r * sin($lon);
	  $z = 0.0;
	  
	  $xequat = $x;
	  $yequat = $y * cos($oblecl) + $z * sin($oblecl);
	  $zequat = $y * sin($oblecl) + $z * cos($oblecl);
	
		$RA   = atan2( $yequat, $xequat );
	  $Decl = asin( $zequat / $r );
	
		$RAhours = r2d($RA)/15;
		
	  $GMST0 = r2d( $L + pi() ) / 15;//
	  $SIDTIME = $GMST0 + $hour + rad2deg($LON)/15;
	  
		$HA = deg2rad(($SIDTIME - $RAhours) *15);
		
		$x = cos($HA) * cos($Decl);
	  $y = sin($HA) * cos($Decl);
	  $z = sin($Decl);
	  
	  $xhor = $x * cos(pi()/2 - $LAT) - $z * sin(pi()/2 - $LAT);
	  $yhor = $y;
	  $zhor = $x * sin(pi()/2 - $LAT) + $z * cos(pi()/2 - $LAT);
	  
	  $azimuth = rad2deg(atan2( $yhor, $xhor ) + pi());
	  $altitude = rad2deg(asin( $zhor ));
	  	  
	}		
	
		
	function r2d($r) {
		$d = rad2deg($r);
		while ($d<0) $d += 360;
		while ($d>360) $d -= 360;
		return $d;
	}
	



	
?>