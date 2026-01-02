<?php

/**
 * Modernized GIS Geometry Library (WKT, GeoJSON, KML, GPX)
 */

namespace GIS;

enum GeometryType: string {
    case Point = 'Point';
    case MultiPoint = 'MultiPoint';
    case LineString = 'LineString';
    case MultiLineString = 'MultiLineString';
    case LinearRing = 'LinearRing';
    case Polygon = 'Polygon';
    case MultiPolygon = 'MultiPolygon';
    case GeometryCollection = 'GeometryCollection';
}

// --- Exceptions ---

class GISException extends \Exception {}
class UnimplementedMethod extends GISException {}
class InvalidText extends GISException {}
class InvalidFeature extends GISException {}
class OutOfRangeCoord extends GISException {}

// --- Base Classes ---

abstract class Decoder {
    abstract public static function geomFromText(string $text): Geometry;
}

abstract class Geometry {
    public const NAME = "";

    abstract public function toGeoJSON(): string;
    abstract public function toKML(): string;
    abstract public function toWKT(): string;

    public function __toString(): string {
        return $this->toWKT();
    }
}

// --- Implementation: Point ---

class Point extends Geometry {
    public const NAME = "Point";
    public readonly float $lon;
    public readonly float $lat;

    public function __construct(array $coords) {
        if (count($coords) < 2) {
            throw new InvalidFeature("Point must have at least two coordinates.");
        }

        [$lon, $lat] = array_map('floatval', $coords);

        if ($lon < -180 || $lon > 180) throw new OutOfRangeCoord("Invalid longitude: $lon");
        if ($lat < -90 || $lat > 90) throw new OutOfRangeCoord("Invalid latitude: $lat");

        $this->lon = $lon;
        $this->lat = $lat;
    }

    public function toWKT(): string {
        return "POINT({$this->lon} {$this->lat})";
    }

    public function toGeoJSON(): string {
        return json_encode([
            'type' => 'Point',
            'coordinates' => [$this->lon, $this->lat]
        ]);
    }

    public function toKML(): string {
        return "<Point><coordinates>{$this->lon},{$this->lat}</coordinates></Point>";
    }

    public function equals(Geometry $geom): bool {
        return $geom instanceof self && $geom->lat === $this->lat && $geom->lon === $this->lon;
    }
}

// --- Implementation: Collections ---

abstract class Collection extends Geometry {
    /** @param Geometry[] $components */
    public function __construct(public readonly array $components) {}

    public function toWKT(): string {
        $type = strtoupper(static::NAME);
        $parts = array_map(fn($g) => $this->getWktContent($g), $this->components);
        return "$type(" . implode(',', $parts) . ")";
    }

    private function getWktContent(Geometry $geom): string {
        if ($geom instanceof Point) return "{$geom->lon} {$geom->lat}";
        // Recursively strip the Type() wrapper for nested WKT components
        return preg_replace('/^\w+\((.*)\)$/', '($1)', $geom->toWKT());
    }

    public function toGeoJSON(): string {
        $coords = array_map(fn($g) => json_decode($g->toGeoJSON())->coordinates, $this->components);
        return json_encode([
            'type' => static::NAME,
            'coordinates' => $coords
        ]);
    }
}

class LineString extends Collection {
    public const NAME = "LineString";
    public function __construct(array $components) {
        if (count($components) < 2) throw new InvalidFeature("LineString needs >= 2 points");
        parent::__construct($components);
    }
    
    public function toKML(): string {
        $coords = implode(" ", array_map(fn($p) => "{$p->lon},{$p->lat}", $this->components));
        return "<LineString><coordinates>$coords</coordinates></LineString>";
    }
}

// --- Decoder: WKT ---

class WKT extends Decoder {
    public static function geomFromText(string $text): Geometry {
        $text = trim($text);
        if (!preg_match('/^(\w+)\s*\((.*)\)$/si', $text, $matches)) {
            throw new InvalidText("Invalid WKT format");
        }

        $type = GeometryType::tryFrom(ucfirst(strtolower($matches[1])));
        $data = $matches[2];

        return match ($type) {
            GeometryType::Point => new Point(preg_split('/\s+/', trim($data))),
            GeometryType::LineString => new LineString(self::parsePointList($data)),
            GeometryType::Polygon => self::parsePolygon($data),
            default => throw new UnimplementedMethod("Type $matches[1] not fully implemented in this snippet"),
        };
    }

    private static function parsePointList(string $str): array {
        return array_map(fn($p) => new Point(preg_split('/\s+/', trim($p))), explode(',', $str));
    }

    private static function parsePolygon(string $str): Polygon {
        // Simple logic for nested rings
        preg_match_all('/\((.*?)\)/', $str, $matches);
        $rings = array_map(fn($m) => new LinearRing(self::parsePointList($m)), $matches[1]);
        return new Polygon($rings);
    }
}

// --- Stub classes for hierarchy logic ---
class LinearRing extends LineString { public const NAME = "LinearRing"; }
class Polygon extends Collection { public const NAME = "Polygon"; }
