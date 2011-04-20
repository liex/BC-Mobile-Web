<?php

/*
http://trac.osgeo.org/proj/wiki/GenParms

+a         Semimajor radius of the ellipsoid axis
+alpha     ? Used with Oblique Mercator and possibly a few others
+axis      Axis orientation (new in 4.8.0)
+b         Semiminor radius of the ellipsoid axis
+datum     Datum name (see `proj -ld`)
+ellps     Ellipsoid name (see `proj -le`)
+k         Scaling factor (old name)
+k_0       Scaling factor (new name)
+lat_0     Latitude of origin
+lat_1     Latitude of first standard parallel
+lat_2     Latitude of second standard parallel
+lat_ts    Latitude of true scale
+lon_0     Central meridian
+lonc      ? Longitude used with Oblique Mercator and possibly a few others
+lon_wrap  Center longitude to use for wrapping (see below)
+nadgrids  Filename of NTv2 grid file to use for datum transforms (see below)
+no_defs   Don't use the /usr/share/proj/proj_def.dat defaults file
+over      Allow longitude output outside -180 to 180 range, disables wrapping (see below)
+pm        Alternate prime meridian (typically a city name, see below)
+proj      Projection name (see `proj -l`)
+south     Denotes southern hemisphere UTM zone
+to_meter  Multiplier to convert map units to 1.0m
+towgs84   3 or 7 term datum transform parameters (see below)
+units     meters, US survey feet, etc.
+x_0       False easting
+y_0       False northing
+zone      UTM zone

*/

class MapProjection
{
    private $proj;

    // raw cartesian points (-pi, pi), intermediate products between
    // adjustedX, adjustedY and phi, lambda
    private $x = NULL;
    private $y = NULL;

    // x and y after accounting for ellipsoid, false easting/northing, and units
    private $adjustedX;
    private $adjustedY;
    
    private $phi = NULL; // latitude in radians
    private $lambda = NULL; // longitude in radians

    // ellipsoid properties
    // our current formulas assume the earth is spheroid with radius equal to seimimajor axis
    private $semiMajorAxis = 1;
    private $semiMinorAxis = 1;
    private $eccentricity = 0;

    private $falseEasting = 0;
    private $falseNorthing = 0;

    private $centralMeridian = 0;
    private $originLatitude = 0;

    private $standardParallel1 = 0;
    private $standardParallel2 = 0;

    private $unitsPerMeter = 1;

    // not using this yet until we need one of the projections
    // with k < 1.0
    private $scaleFactor;

    public function isGeographic()
    {
        return $this->proj == 'longlat';
    }

    public function setXY(Array $xy)
    {
        if (isset($xy['x'], $xy['y'])) {
            $this->adjustedX = $xy['x'];
            $this->adjustedY = $xy['y'];
        }
    }

    public function setLatLon(Array $latlon)
    {
        if (isset($latlon['lat'], $latlon['lon'])) {
            $this->phi = $latlon['lat'] / 180 * M_PI;
            $this->lambda = $latlon['lon'] / 180 * M_PI;
        }
    }
    
    private function lccM($phi)
    {
        return cos($phi) / sqrt(1 - pow($this->eccentricity * sin($phi), 2));
    }
    
    private function lccT($phi)
    {
        return tan(M_PI_4 - $phi / 2) / $this->lccESAdjustment($phi);
    }
    
    private function lccESAdjustment($phi)
    {
        $eSinPhi = $this->eccentricity * sin($phi);
        return pow((1 - $eSinPhi) / (1 + $eSinPhi), $this->eccentricity / 2);
    }

    private function forwardProject()
    {
        switch ($this->proj) {

            case 'longlat': // 429 SRIDs
                $this->x = $this->lambda * 180 / M_PI;
                $this->y = $this->phi * 180 / M_PI;
                break;

            case 'lcc': // 716 SRIDs; http://www.linz.govt.nz/geodetic/conversion-coordinates/projection-conversions/lambert-conformal-conic/index.aspx
                $m1 = $this->lccM($this->standardParallel1);
                $t1 = $this->lccT($this->standardParallel1);
                $n = (log($m1) - log($this->lccM($this->standardParallel2))) / (log($t1) - log($this->lccT($this->standardParallel2)));
                $F = $m1 / ($n * pow($t1, $n));
                $rho = $this->semiMajorAxis * $F * pow($this->lccT($this->phi), $n);
                $rho0 = $this->semiMajorAxis * $F * pow($this->lccT($this->originLatitude), $n);

                $gamma = $n * ($this->lambda - $this->centralMeridian);
                $this->x = $rho * sin($gamma);
                $this->y = $rho0 - $rho * cos($gamma);
                
                break;

            case 'merc': // 15 SRIDs; http://en.wikipedia.org/wiki/Mercator_projection
                $this->x = ($this->lambda - $this->centralMeridian) * $this->semiMajorAxis;
                $this->y = (log(tan(M_PI_4 + $this->phi / 2))) * $this->semiMajorAxis;
                break;

            case 'tmerc': // 1501 SRIDs; http://en.wikipedia.org/wiki/Transverse_Mercator_projection
            case 'utm': // 930 SRIDs
            case 'stere': // 29
            case 'cass': // 20
            case 'aea': // 20 SRIDs; http://en.wikipedia.org/wiki/Albers_projection
            case 'omerc': // 17
            case 'laea': // 10 SRIDs; http://en.wikipedia.org/wiki/Lambert_azimuthal_equal-area_projection
            case 'somerc': // 5
            case 'poly': // 2
            case 'eqc': // 2
            case 'cea': // 2 SRIDs; http://en.wikipedia.org/wiki/Cylindrical_equal-area_projection
            case 'nzmg': // 1
            case 'krovak': // 1
            default:
                throw new Exception("projection not implemented for {$this->proj}");
                break;
        }
    }

    private function reverseProject()
    {
        switch ($this->proj) {

            case 'longlat': // 429 SRIDs
                $this->lambda = $this->x / 180 * M_PI;
                $this->phi = $this->y / 180 * M_PI;
                break;

            case 'lcc': // 716 SRIDs; http://www.linz.govt.nz/geodetic/conversion-coordinates/projection-conversions/lambert-conformal-conic/index.aspx
                $m1 = $this->lccM($this->standardParallel1);
                $t1 = $this->lccT($this->standardParallel1);
                $n = (log($m1) - log($this->lccM($this->standardParallel2))) / (log($t1) - log($this->lccT($this->standardParallel2)));
                $F = $m1 / ($n * pow($t1, $n));
                $rho0 = $this->semiMajorAxis * $F * pow($this->lccT($this->originLatitude), $n);
            
                // different from the forward projection
                $rhoPrime = sqrt(pow($this->x, 2) + pow($rho0 - $this->y, 2));
                if ($n < 0) $rhoPrime = -$rhoPrime;
                if ($n == 0) $rhoPrime = 0;
                
                $tPrime = pow($rhoPrime / ($F * $this->semiMajorAxis), 1 / $n);
                $gammaPrime = atan($this->x / ($rho0 - $this->y));
                
                $this->lambda = $gammaPrime / $n + $this->centralMeridian;
                $this->phi = M_PI_2 - 2 * atan($tPrime);
                for ($i = 0; $i < 2; $i++) {
                    $this->phi = M_PI_2 - 2 * atan($tPrime * $this->lccESAdjustment($this->phi));
                }
            
                break;

            case 'merc': // 15 SRIDs; http://en.wikipedia.org/wiki/Mercator_projection
                $this->lambda = $this->x / $this->semiMajorAxis + $this->centralMeridian;
                $this->phi = 2 * (atan(exp($this->y / $this->semiMajorAxis)) - M_PI / 4);
                break;

            case 'tmerc': // 1501 SRIDs; http://en.wikipedia.org/wiki/Transverse_Mercator_projection
            case 'utm': // 930 SRIDs
            case 'stere': // 29
            case 'cass': // 20
            case 'aea': // 20 SRIDs; http://en.wikipedia.org/wiki/Albers_projection
            case 'omerc': // 17
            case 'laea': // 10 SRIDs; http://en.wikipedia.org/wiki/Lambert_azimuthal_equal-area_projection
            case 'somerc': // 5
            case 'poly': // 2
            case 'eqc': // 2
            case 'cea': // 2 SRIDs; http://en.wikipedia.org/wiki/Cylindrical_equal-area_projection
            case 'nzmg': // 1
            case 'krovak': // 1
            default:
                throw new Exception("reverse projection not implemented for {$this->proj}");
                break;
        }
    }

    public function getXY()
    {
        if ($this->adjustedX === NULL || $this->adjustedY === NULL) {
            if ($this->lambda === NULL || $this->phi === NULL) {
                throw new Exception("source points not set");
            }
            $this->forwardProject();
            $this->adjustedX = ($this->x + $this->falseEasting) * $this->unitsPerMeter;
            $this->adjustedY = ($this->y + $this->falseNorthing) * $this->unitsPerMeter;
        }

        return array(
            'lon' => $this->adjustedX,
            'lat' => $this->adjustedY,
            );
    }

    public function getLatLon()
    {
        if ($this->lambda === NULL || $this->phi === NULL) {
            if ($this->adjustedX === NULL || $this->adjustedY === NULL) {
                throw new Exception("source points not set");
            }
            
            $this->x = $this->adjustedX / $this->unitsPerMeter - $this->falseEasting;
            $this->y = $this->adjustedY / $this->unitsPerMeter - $this->falseNorthing;
            $this->reverseProject();
        }

        return array(
            'lat' => $this->phi * 180 / M_PI,
            'lon' => $this->lambda * 180 / M_PI,
            );
    }

    public function __construct($proj4String)
    {
        $params = self::parseProj4String($proj4String);
        $this->proj = $params['+proj'];

        // plug in pre-calculated values for eccentricity
        // which is just sqrt((a^2 - b^2) / a^2) where a is major axis and b is minor axis 
        if (isset($params['+ellps'])) {
            switch ($params['+ellps']) {
                case 'GRS80': // 1252 SRIDs; http://en.wikipedia.org/wiki/GRS_80
                    $this->semiMajorAxis = 6378137;
                    $this->semiMinorAxis = 6356752.31414;
                    $this->eccentricity = 0.08181919;
                    break;
                case 'krass': // 562
                case 'intl': // 379
                    break;
                case 'WGS84': // 322
                    $this->semiMajorAxis = 6378137;
                    $this->semiMinorAxis = 6378137;
                    break;
                case 'clrk66': // 263
                case 'WGS72': // 248
                case 'bessel': // 155
                case 'clrk80': //128
                case 'aust': // 46
                case 'GRS67': // 19
                case 'helmert': // 13
                case 'evrstSS': // 7
                case 'airy': // 7
                case 'bess': // 3
                case 'WGS66': // 3
                    break;
            }

        } elseif (isset($params['+datum'])) {
            switch ($params['+datum']) {
                case 'NAD83': // 322
                    $this->semiMajorAxis = 6378137;
                    $this->semiMinorAxis = 6356752.31414;
                    $this->eccentricity = 0.08181919;
                    break;
                case 'WGS84': // 246
                    $this->semiMajorAxis = 6378137;
                    $this->semiMinorAxis = 6378137;
                    break;
                case 'NAD27': // 177
                case 'nzgd49': // 35
                case 'potsdam': // 11
                case 'OSGB36': // 1
                    break;
            }

        } else {
            if (isset($params['+a'])) {
                $this->semiMajorAxis = $params['+a'];
            }

            if (isset($params['+b'])) {
                $this->semiMinorAxis = $params['+b'];
            }
        }

        if (isset($params['+to_meter'])) {
            $this->unitsPerMeter = 1 / $params['+to_meter'];

        } else if (isset($params['+units'])) {
            switch ($params['+units']) {
                case 'us-ft':
                case 'ft':
                    $this->unitsPerMeter = 3.2808399; // both are 3.2803 in postgis
                    break;
                case 'm':
                    break;
            }
        }

        if (isset($params['+lat_1'])) {
            $this->standardParallel1 = $params['+lat_1'] / 180 * M_PI;
        }

        if (isset($params['+lat_2'])) {
            $this->standardParallel2 = $params['+lat_2'] / 180 * M_PI;
        }

        if (isset($params['+lat_0'])) {
            $this->originLatitude = $params['+lat_0'] / 180 * M_PI;
        }

        if (isset($params['+lon_0'])) {
            $this->centralMeridian = $params['+lon_0'] / 180 * M_PI;
        }


        if (isset($params['+x_0'])) { // these are always in meters
            $this->falseEasting = $params['+x_0'];
        }
        if (isset($params['+y_0'])) { // these are always in meters
            $this->falseNorthing = $params['+y_0'];
        }
        /*
        if (isset($params['+k'])) {
            $this->scaleFactor = $params['+k'];
        }
        */
    }

    public static function parseProj4String($proj4String)
    {
        $params = array();
        $args = preg_split("/\s+/", $proj4String, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($args as $arg) {
            $argParts = explode('=', $arg);
            if (count($argParts) == 2) {
                $params[ $argParts[0] ] = $argParts[1];
            }
        }
        return $params;
    }
    
    private function logResults() {
        error_log("adjustedX: {$this->adjustedX}, adjustedY: {$this->adjustedY}");
        error_log("x: {$this->x}, y: {$this->y}");
        error_log("phi: {$this->phi}, lambda: {$this->lambda}");
        $lat = $this->phi * 180 / M_PI; $lon = $this->lambda * 180 / M_PI;
        error_log("latitude: $lat, longitude: $lon");
    }
}



