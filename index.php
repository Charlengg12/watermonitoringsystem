<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Quality Monitoring Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <header>
        <div class="header-content">
            <h1>Water Quality Analysis</h1>
            <div class="status-indicator">
                <span id="overall-connection-status" class="offline">CONNECTING...</span>
            </div>
        </div>
    </header>

    <main class="dashboard">

        <div class="gauges-container">

            <div class="gauge-card">
                <div class="gauge" id="phGauge">
                    <div class="gauge-body">
                        <div class="gauge-fill"></div>
                        <div class="gauge-cover"></div>
                        <div class="gauge-indicator" id="phIndicator"></div>
                    </div>
                    <div class="gauge-value" id="phValueDisplay">---</div>
                </div>
                <h3 class="gauge-label">pH</h3>
                <p class="gauge-status connecting" id="phStatusDisplay">Connecting...</p>
            </div>

            <div class="gauge-card">
                <div class="gauge" id="tdsGauge">
                    <div class="gauge-body">
                        <div class="gauge-fill"></div>
                        <div class="gauge-cover"></div>
                        <div class="gauge-indicator" id="tdsIndicator"></div>
                    </div>
                    <div class="gauge-value" id="tdsValueDisplay">---</div>
                </div>
                <h3 class="gauge-label">TDS (mg/L)</h3>
                <p class="gauge-status connecting" id="tdsStatusDisplay">Connecting...</p>
            </div>

            <div class="gauge-card">
                <div class="gauge" id="turbidityGauge">
                    <div class="gauge-body">
                        <div class="gauge-fill"></div>
                        <div class="gauge-cover"></div>
                        <div class="gauge-indicator" id="turbidityIndicator"></div>
                    </div>
                    <div class="gauge-value" id="turbidityValueDisplay">---</div>
                </div>
                <h3 class="gauge-label">Turbidity (NTU)</h3>
                <p class="gauge-status connecting" id="turbidityStatusDisplay">Connecting...</p>
            </div>

            <div class="gauge-card">
                <div class="gauge" id="leadGauge">
                    <div class="gauge-body">
                        <div class="gauge-fill"></div>
                        <div class="gauge-cover"></div>
                        <div class="gauge-indicator" id="leadIndicator"></div>
                    </div>
                    <div class="gauge-value" id="leadValueDisplay">---</div>
                </div>
                <h3 class="gauge-label">Lead (mg/L)</h3>
                <p class="gauge-status connecting" id="leadStatusDisplay">Connecting...</p>
            </div>

            <div class="gauge-card">
                <div class="gauge" id="colorGauge">
                    <div class="gauge-body">
                        <div class="gauge-fill"></div>
                        <div class="gauge-cover"></div>
                        <div class="gauge-indicator" id="colorIndicator"></div>
                    </div>
                    <div class="gauge-value" id="colorValueDisplay">---</div>
                </div>
                <h3 class="gauge-label">Color Analysis</h3>
                <p class="gauge-status connecting" id="colorStatusDisplay">Connecting...</p>
            </div>

        </div>
        <div class="last-update-bar">
            <p id="last-update-display">Waiting for data...</p>
        </div>

    </main>

    <script src="script.js"></script>
</body>

</html>