# GIS Boundary Files

Place the Koronadal barangay boundary GeoJSON file here:

```text
public/gis/koronadal-barangays.geojson
```

The map endpoint will use this file automatically when it exists. Each feature should contain a barangay name in one of these properties:

```text
name, NAME, barangay, BARANGAY, brgy, BRGY, ADM4_EN, ADM4_NAME
```

If the file is missing, the app falls back to generated approximate barangay zones based on the seeded barangay coordinates.
