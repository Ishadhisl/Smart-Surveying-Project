<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <title>AR Boundary Viewer - Smart Survey</title>
    <!-- A-Frame itself -->
    <script src="https://aframe.io/releases/1.3.0/aframe.min.js"></script>
    <!-- AR.js Location based AR -->
    <script type='text/javascript' src='https://raw.githack.com/AR-js-org/AR.js/master/three.js/build/ar-threex-location-only.js'></script>
    <script type='text/javascript' src='https://raw.githack.com/AR-js-org/AR.js/master/aframe/build/aframe-ar.js'></script>
    <style>
      body { margin: 0; overflow: hidden; font-family: 'Inter', sans-serif; }
      .ui-overlay {
          position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); width: 90%; z-index: 999;
          background: rgba(15, 23, 42, 0.85); color: white; padding: 20px; text-align: center;
          border-radius: 20px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);
      }
      .back-btn {
          position: fixed; left: 20px; top: 20px; background: rgba(255,255,255,0.9); color: #0f172a;
          padding: 10px 20px; text-decoration: none; border-radius: 12px; font-weight: 900; z-index: 1000;
          box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-size: 14px; text-transform: uppercase; letter-spacing: 1px;
      }
      .point-count {
          font-size: 24px; font-weight: 900; color: #14b8a6; margin: 0 0 5px 0;
      }
      .info-text {
          font-size: 12px; margin: 0; color: rgba(255,255,255,0.7); font-weight: 600; line-height: 1.5;
      }
    </style>
  </head>

  <body style="margin: 0; overflow: hidden;">
    <a href="{{ route('survey.show', $survey) }}" class="back-btn">&larr; Dashboard</a>
    
    <div class="ui-overlay">
        <h3 class="point-count">{{ count($points) }} Boundary Points</h3>
        <p class="info-text">Look around your land using the camera. The red virtual pillars represent your exact surveyed boundaries via GPS.</p>
    </div>

    <!-- AR Scene -->
    <a-scene
      vr-mode-ui="enabled: false"
      arjs="sourceType: webcam; videoTexture: true; debugUIEnabled: false;"
      renderer="antialias: true; alpha: true">

      <a-camera gps-camera="simulateLatitude: {{ count($points) > 0 ? $points[0]->latitude - 0.0001 : 0 }}; simulateLongitude: {{ count($points) > 0 ? $points[0]->longitude - 0.0001 : 0 }}" rotation-reader></a-camera>

      @foreach($points as $point)
        <!-- Virtual Boundary Pillar (Glowing Cylinder) -->
        <a-cylinder 
            gps-entity-place="latitude: {{ $point->latitude }}; longitude: {{ $point->longitude }};"
            color="#0ea5e9"
            radius="0.5"
            height="15"
            position="0 7.5 0"
            opacity="0.7"
            transparent="true"
            animation="property: opacity; to: 0.3; dir: alternate; dur: 1500; loop: true">
        </a-cylinder>

        <!-- Base Ring -->
        <a-ring
            gps-entity-place="latitude: {{ $point->latitude }}; longitude: {{ $point->longitude }};"
            color="#38bdf8"
            radius-inner="0.6"
            radius-outer="1.2"
            rotation="-90 0 0"
            position="0 0.1 0">
        </a-ring>

        <!-- Floating Number Label above the Pillar -->
        <a-text 
            gps-entity-place="latitude: {{ $point->latitude }}; longitude: {{ $point->longitude }};"
            value="Point {{ $point->point_order }}" 
            color="#ffffff" 
            scale="10 10 10"
            position="0 18 0"
            align="center"
            look-at="[gps-camera]">
        </a-text>
      @endforeach
    </a-scene>
  </body>
</html>
