<!DOCTYPE html>
<html>
<head>
    <title>Debug API - Trend Analysis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #333;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        button:hover {
            background: #45a049;
        }
        button.secondary {
            background: #2196F3;
        }
        button.secondary:hover {
            background: #0b7dda;
        }
        .result {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
        }
        .error {
            background: #ffebee;
            border-color: #ffcdd2;
            color: #c62828;
        }
        .success {
            background: #e8f5e9;
            border-color: #c8e6c9;
            color: #2e7d32;
        }
        .info {
            background: #e3f2fd;
            border-color: #bbdefb;
            color: #1565c0;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.success {
            background: #4CAF50;
            color: white;
        }
        .status.error {
            background: #f44336;
            color: white;
        }
        .endpoint-info {
            margin: 10px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug API - Trend Analysis</h1>
        <p>Gunakan halaman ini untuk menguji endpoint API dan memastikan data tersedia dengan benar.</p>

        <!-- Check Database Connection -->
        <div class="test-section">
            <h3>1. Database Connection & Views</h3>
            <button onclick="checkDatabase()">Check Database</button>
            <div id="db-result" class="result" style="display:none;"></div>
        </div>

        <!-- Test Overview API -->
        <div class="test-section">
            <h3>2. Test Overview API</h3>
            <div class="endpoint-info">/api/trend-analysis/overview</div>
            <button onclick="testOverviewAPI('yearly')">Test Yearly View</button>
            <button onclick="testOverviewAPI('monthly')" class="secondary">Test Monthly View</button>
            <div id="overview-result" class="result" style="display:none;"></div>
        </div>

        <!-- Test Category API -->
        <div class="test-section">
            <h3>3. Test Category API</h3>
            <div class="endpoint-info">/api/trend-analysis/category/{id}</div>
            <input type="text" id="category-id" placeholder="Enter Category ID" style="padding: 8px; margin-right: 10px;">
            <button onclick="testCategoryAPI()">Test Category</button>
            <div id="category-result" class="result" style="display:none;"></div>
        </div>

        <!-- Test Search API -->
        <div class="test-section">
            <h3>4. Test Search API</h3>
            <div class="endpoint-info">/api/trend-analysis/search</div>
            <input type="text" id="search-query" placeholder="Enter search term" style="padding: 8px; margin-right: 10px;">
            <button onclick="testSearchAPI()">Test Search</button>
            <div id="search-result" class="result" style="display:none;"></div>
        </div>

        <!-- Raw SQL Test -->
        <div class="test-section">
            <h3>5. Raw SQL Test</h3>
            <button onclick="testRawSQL()">Test Raw Query</button>
            <div id="sql-result" class="result" style="display:none;"></div>
        </div>
    </div>

    <script>
        const API_BASE = '/api/trend-analysis';

        // Helper function to display results
        function showResult(elementId, data, isError = false) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.className = 'result ' + (isError ? 'error' : 'success');
            element.textContent = typeof data === 'object' ? JSON.stringify(data, null, 2) : data;
        }

        // Check database connection and views
        async function checkDatabase() {
            try {
                const response = await fetch('/api/debug/check-database');
                const data = await response.json();
                showResult('db-result', data);
            } catch (error) {
                showResult('db-result', 'Error: ' + error.message, true);
            }
        }

        // Test Overview API
        async function testOverviewAPI(view = 'yearly') {
            try {
                const params = new URLSearchParams({
                    years: 3,
                    view: view,
                    month: new Date().getMonth() + 1
                });
                
                const url = `${API_BASE}/overview?${params}`;
                console.log('Testing URL:', url);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${data.message || 'Unknown error'}`);
                }
                
                showResult('overview-result', {
                    url: url,
                    status: response.status,
                    success: data.success,
                    data: data.data,
                    dataStructure: {
                        hasCategories: !!(data.data && data.data.categories),
                        hasSeries: !!(data.data && data.data.series),
                        categoriesCount: data.data?.categories?.length || 0,
                        seriesCount: data.data?.series?.length || 0
                    }
                });
            } catch (error) {
                showResult('overview-result', 'Error: ' + error.message, true);
            }
        }

        // Test Category API
        async function testCategoryAPI() {
            try {
                const categoryId = document.getElementById('category-id').value;
                if (!categoryId) {
                    throw new Error('Please enter a category ID');
                }
                
                const params = new URLSearchParams({
                    years: 3,
                    view: 'yearly'
                });
                
                const url = `${API_BASE}/category/${categoryId}?${params}`;
                console.log('Testing URL:', url);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${data.message || 'Unknown error'}`);
                }
                
                showResult('category-result', {
                    url: url,
                    status: response.status,
                    data: data
                });
            } catch (error) {
                showResult('category-result', 'Error: ' + error.message, true);
            }
        }

        // Test Search API
        async function testSearchAPI() {
            try {
                const query = document.getElementById('search-query').value;
                if (!query || query.length < 2) {
                    throw new Error('Please enter at least 2 characters');
                }
                
                const url = `${API_BASE}/search?q=${encodeURIComponent(query)}`;
                console.log('Testing URL:', url);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${data.message || 'Unknown error'}`);
                }
                
                showResult('search-result', {
                    url: url,
                    status: response.status,
                    resultsCount: data.data?.length || 0,
                    data: data
                });
            } catch (error) {
                showResult('search-result', 'Error: ' + error.message, true);
            }
        }

        // Test Raw SQL
        async function testRawSQL() {
            try {
                const response = await fetch('/api/debug/test-sql');
                const data = await response.json();
                showResult('sql-result', data);
            } catch (error) {
                showResult('sql-result', 'Error: ' + error.message, true);
            }
        }

        // Auto-test on load
        window.addEventListener('DOMContentLoaded', function() {
            console.log('Debug page loaded. API Base:', API_BASE);
        });
    </script>
</body>
</html>