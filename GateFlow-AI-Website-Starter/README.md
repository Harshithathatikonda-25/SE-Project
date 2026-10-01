# GateFlow-AI Website

Starter PHP + MySQL dashboard for the GateFlow-AI campus gate monitoring project.

## Modules included
- Dashboard
- Vehicle Management
- Entry / Exit Management
- ANPR interface
- CCTV monitoring interface
- Crowd / Traffic monitoring
- Alerts and incidents
- Reports
- User management

## Run with XAMPP
1. Copy the `GateFlow-AI` folder into `C:/xampp/htdocs/`.
2. Start Apache and MySQL.
3. Open phpMyAdmin.
4. Import `database.sql`.
5. Open `http://localhost/GateFlow-AI/`.

## Important
The ANPR, CCTV and crowd-monitoring pages are prepared as integration interfaces. Connect your actual Python/OpenCV/AI modules next; the starter does not pretend to perform real AI inference by itself.
