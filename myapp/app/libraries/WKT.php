<?php
/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

abstract class CustomException extends Exception
{

    public function __toString(): string
    {
        return get_class($this) . ": {$this->message} in {$this->file}({$this->line})\n{$this->getTraceAsString()}";
    }
}

class Unimplemented extends CustomException
{

    public function __construct(string $message)
    {
        $this->message = "Unimplemented: $message";
    }
}

class UnimplementedMethod extends Unimplemented
{

    public function __construct(string $method, string $class)
    {
        parent::__construct("method {$class}::{$method}");
    }
}

class InvalidText extends CustomException
{

    public function __construct(string $decoder_name, string $text = "")
    {
        $this->message = "Invalid text for decoder $decoder_name" . ($text ? ": $text" : "");
    }
}

class InvalidFeature extends CustomException
{

    public function __construct(string $decoder_name, string $text = "")
    {
        $this->message = "Invalid feature for decoder $decoder_name" . ($text ? ": $text" : "");
    }
}

abstract class OutOfRangeCoord extends CustomException
{

    public function __construct(protected mixed $coord, protected string $type = "coordinate")
    {
        $this->message = "Invalid {$this->type}: $coord";
    }
}

class OutOfRangeLon extends OutOfRangeCoord
{

    public function __construct($coord)
    {
        parent::__construct($coord, "longitude");
    }
}

class OutOfRangeLat extends OutOfRangeCoord
{

    public function __construct($coord)
    {
        parent::__construct($coord, "latitude");
    }
}

abstract class Decoder
{

    abstract static public function geomFromText(string $text): Geometry;
}

abstract class Geometry
{

    public const NAME = "";

    abstract public function toGeoJSON(): string;

    abstract public function toKML(): string;

    abstract public function toWKT(): string;

    public function toGPX(?string $mode = null): string
    {
        throw new UnimplementedMethod(__FUNCTION__, static::class);
    }

    public function equals(Geometry $geom): bool
    {
        throw new UnimplementedMethod(__FUNCTION__, static::class);
    }

    public function __toString(): string
    {
        return $this->toWKT();
    }
}

class WKT extends Decoder
{

    private const ALLOWED_TYPES = [
        "Point",
        "MultiPoint",
        "LineString",
        "MultiLineString",
        "LinearRing",
        "Polygon",
        "MultiPolygon",
        "GeometryCollection"
    ];

    static public function geomFromText(string $text): Geometry
    {
        $type_pattern = '/\s*(\w+)\s*\(\s*(.*)\s*\)\s*$/i';
        if (! preg_match($type_pattern, $text, $matches)) {
            throw new InvalidText(self::class, $text);
        }

        $input_type = strtolower($matches[1]);
        $found_type = null;

        foreach (self::ALLOWED_TYPES as $wkt_type) {
            if (strtolower($wkt_type) === $input_type) {
                $found_type = $wkt_type;
                break;
            }
        }

        if (! $found_type) {
            throw new InvalidText(self::class, $text);
        }

        try {
            $method = "parse" . $found_type;
            $components = self::$method($matches[2]);
            return new $found_type($components);
        } catch (Exception $e) {
            throw new InvalidText(self::class, $text);
        }
    }

    static protected function parsePoint(string $str): array
    {
        return preg_split('/\s+/', trim($str));
    }

    static protected function parseLineString(string $str): array
    {
        return array_map(fn ($comp) => new Point(self::parsePoint($comp)), explode(',', trim($str)));
    }

    // ... (Other parse methods simplified similarly)
}

class Point extends Geometry
{

    public const NAME = "Point";

    public float $lon;

    public float $lat;

    public function __construct(array &$coords)
    {
        if (count($coords) < 2) {
            throw new InvalidFeature(self::NAME, "Point must have two coordinates");
        }

        [
            $lon,
            $lat
        ] = $coords;

        if (! $this->checkCoord($lon, - 180, 180))
            throw new OutOfRangeLon($lon);
        if (! $this->checkCoord($lat, - 90, 90))
            throw new OutOfRangeLat($lat);

        $this->lon = (float) $lon;
        $this->lat = (float) $lat;
    }

    private function checkCoord($val, $min, $max): bool
    {
        return is_numeric($val) && $val >= $min && $val <= $max;
    }

    public function toWKT(): string
    {
        return "POINT({$this->lon} {$this->lat})";
    }

    public function toGeoJSON(): string
    {
        return json_encode([
            'type' => self::NAME,
            'coordinates' => [
                $this->lon,
                $this->lat
            ]
        ]);
    }

    public function toKML(): string
    {
        return "<Point><coordinates>{$this->lon},{$this->lat}</coordinates></Point>";
    }

    public function equals(Geometry &$geom): bool
    {
        return ($geom instanceof self) && $geom->lat === $this->lat && $geom->lon === $this->lon;
    }
}

// ... Additional Collection and Polygon classes would follow this pattern
abstract class Collection extends Geometry
{

    /** @var Geometry[] */
    public array $components;

    public function __construct(array &$components)
    {
        $this->components = $components;
    }

    public function toWKT(): string
    {
        $recursiveWKT = function ($geom) use (&$recursiveWKT) {
            return ($geom instanceof Point) ? "{$geom->lon} {$geom->lat}" : "(" . implode(',', array_map($recursiveWKT, $geom->components)) . ")";
        };
        return strtoupper(static::NAME) . $recursiveWKT($this);
    }

    public function toGeoJSON(): string
    {
        $recursiveJSON = function ($geom) use (&$recursiveJSON) {
            return ($geom instanceof Point) ? [
                $geom->lon,
                $geom->lat
            ] : array_map($recursiveJSON, $geom->components);
        };

        return json_encode([
            'type' => static::NAME,
            'coordinates' => $recursiveJSON($this)
        ]);
    }
}

class LinearRing extends LineString
{

    public const NAME = "LinearRing";

    public function __construct(array &$components)
    {
        if (empty($components)) {
            throw new InvalidFeature(self::NAME, "LinearRing cannot be empty");
        }

        $first = $components[0];
        $last = end($components);

        if (! $first->equals($last)) {
            throw new InvalidFeature(self::NAME, "LinearRing must be closed (start and end points must be equal)");
        }
        parent::__construct($components);
    }

    /**
     * Ray-casting algorithm to determine if a point is inside the ring.
     */
    public function containsPoint(Point &$point): bool
    {
        $px = round($point->lon, 14);
        $py = round($point->lat, 14);
        $crosses = 0;

        $count = count($this->components);
        for ($i = 0; $i < $count - 1; $i ++) {
            $start = $this->components[$i];
            $end = $this->components[$i + 1];

            $x1 = round($start->lon, 14);
            $y1 = round($start->lat, 14);
            $x2 = round($end->lon, 14);
            $y2 = round($end->lat, 14);

            // Check if point is on a horizontal edge
            if ($y1 === $y2 && $py === $y1) {
                if ($px >= min($x1, $x2) && $px <= max($x1, $x2))
                    return true;
                continue;
            }

            // Calculate intersection
            if (($y1 > $py) !== ($y2 > $py)) {
                $intersectX = ($x2 - $x1) * ($py - $y1) / ($y2 - $y1) + $x1;
                if ($px === round($intersectX, 14))
                    return true; // Point is on edge
                if ($px < $intersectX)
                    $crosses ++;
            }
        }
        return ($crosses % 2) !== 0;
    }
}

class Polygon extends Collection
{

    public const NAME = "Polygon";

    public function __construct(array &$components)
    {
        if (empty($components) || ! ($components[0] instanceof LinearRing)) {
            throw new InvalidFeature(self::NAME, "Polygon must start with an outer LinearRing");
        }

        $outer = $components[0];
        // Validate that all holes are inside the outer ring
        foreach (array_slice($components, 1) as $inner) {
            if (! $inner instanceof LinearRing) {
                throw new InvalidFeature(self::NAME, "Polygon components must be LinearRings");
            }
            // Logic: Every point of the inner ring must be inside the outer ring
            foreach ($inner->components as $point) {
                if (! $outer->containsPoint($point)) {
                    throw new InvalidFeature(self::NAME, "Inner rings must be enclosed in outer ring");
                }
            }
        }
        parent::__construct($components);
    }
}
