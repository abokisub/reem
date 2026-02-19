<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banks - PointWave API Documentation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1a202c;
            background: #f7fafc;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            padding: 20px;
        }

        /* Sidebar */
        .sidebar {
            position: sticky;
            top: 20px;
            height: fit-content;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .sidebar h3 {
            font-size: 0.875rem;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar li {
            margin-bottom: 8px;
        }

        .sidebar a {
            color: #4a5568;
            text-decoration: none;
            font-size: 0.9rem;
            display: block;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #edf2f7;
            color: #2d3748;
        }

        /* Main Content */
        .main-content {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.5rem;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        h2 {
            font-size: 1.8rem;
            color: #2d3748;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        h3 {
            font-size: 1.3rem;
            color: #2d3748;
            margin: 30px 0 15px;
        }

        p {
            margin-bottom: 15px;
            color: #4a5568;
        }

        /* Code Blocks */
        .code-example {
            margin: 20px 0;
        }

        .code-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: -1px;
        }

        .code-tab {
            padding: 10px 20px;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-bottom: none;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .code-tab.active {
            background: #2d3748;
            color: white;
            border-color: #2d3748;
        }

        pre {
            margin: 0;
            border-radius: 0 6px 6px 6px;
            overflow-x: auto;
        }

        pre code {
            font-size: 0.875rem;
        }

        /* Endpoint Box */
        .endpoint {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4299e1;
        }

        .method {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-right: 10px;
        }

        .method.get {
            background: #48bb78;
            color: white;
        }

        .method.post {
            background: #4299e1;
            color: white;
        }

        /* Parameters Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge.required {
            background: #fc8181;
            color: white;
        }

        .badge.optional {
            background: #f6ad55;
            color: white;
        }

        /* Response Box */
        .response {
            background: #f0fff4;
            border-left: 4px solid #48bb78;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .response h4 {
            color: #22543d;
            margin-bottom: 10px;
        }

        /* Alert Boxes */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid;
        }

        .alert.info {
            background: #ebf8ff;
            border-color: #4299e1;
            color: #2c5282;
        }

        .alert.warning {
            background: #fffaf0;
            border-color: #ed8936;
            color: #7c2d12;
        }

        .alert.success {
            background: #f0fff4;
            border-color: #48bb78;
            color: #22543d;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3>Documentation</h3>
            <ul>
                <li><a href="{{ route('docs.index') }}">Getting Started</a></li>
                <li><a href="{{ route('docs.authentication') }}">Authentication</a></li>
                <li><a href="{{ route('docs.customers') }}">Customers</a></li>
                <li><a href="{{ route('docs.virtual-accounts') }}">Virtual Accounts</a></li>
                <li><a href="/docs/banks" class="active">Banks</a></li>
                <li><a href="{{ route('docs.transfers') }}">Transfers</a></li>
                <li><a href="{{ route('docs.webhooks') }}">Webhooks</a></li>
                <li><a href="{{ route('docs.errors') }}">Error Codes</a></li>
                <li><a href="{{ route('docs.sandbox') }}">Sandbox</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <h1>🏦 Banks</h1>
            <p class="subtitle">Get list of supported Nigerian banks for transfers and account verification</p>

            <div class="alert info">
                <strong>💡 Pro Tip:</strong> Cache the banks list in your application to avoid repeated API calls. The
                list rarely changes.
            </div>

            <!-- Get Banks List -->
            <h2 id="get-banks">Get Banks List</h2>
            <p>Retrieve a list of all supported Nigerian banks. Use this endpoint to populate bank selection dropdowns
                in your application.</p>

            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/api/v1/banks</code>
            </div>

            <h3>Request</h3>
            <div class="code-example">
                <div class="code-tabs">
                    <div class="code-tab active" onclick="showCode('curl-banks')">cURL</div>
                    <div class="code-tab" onclick="showCode('php-banks')">PHP</div>
                    <div class="code-tab" onclick="showCode('node-banks')">Node.js</div>
                    <div class="code-tab" onclick="showCode('python-banks')">Python</div>
                </div>

                <div id="curl-banks" class="code-content">
                    <pre><code class="language-bash">curl -X GET "https://app.pointwave.ng/api/v1/banks" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID"</code></pre>
                </div>

                <div id="php-banks" class="code-content" style="display:none;">
                    <pre><code class="language-php">&lt;?php
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://app.pointwave.ng/api/v1/banks",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer YOUR_SECRET_KEY",
        "x-api-key: YOUR_API_KEY",
        "x-business-id: YOUR_BUSINESS_ID"
    ],
]);

$response = curl_exec($curl);
$banks = json_decode($response, true);

curl_close($curl);

// Display banks
foreach ($banks['data'] as $bank) {
    echo $bank['name'] . " - " . $bank['code'] . "\n";
}</code></pre>
                </div>

                <div id="node-banks" class="code-content" style="display:none;">
                    <pre><code class="language-javascript">const axios = require('axios');

const getBanks = async () => {
    try {
        const response = await axios.get('https://app.pointwave.ng/api/v1/banks', {
            headers: {
                'Authorization': 'Bearer YOUR_SECRET_KEY',
                'x-api-key': 'YOUR_API_KEY',
                'x-business-id': 'YOUR_BUSINESS_ID'
            }
        });
        
        console.log('Banks:', response.data.data);
        return response.data.data;
    } catch (error) {
        console.error('Error:', error.response.data);
    }
};

getBanks();</code></pre>
                </div>

                <div id="python-banks" class="code-content" style="display:none;">
                    <pre><code class="language-python">import requests

url = "https://app.pointwave.ng/api/v1/banks"
headers = {
    "Authorization": "Bearer YOUR_SECRET_KEY",
    "x-api-key": "YOUR_API_KEY",
    "x-business-id": "YOUR_BUSINESS_ID"
}

response = requests.get(url, headers=headers)
banks = response.json()

# Display banks
for bank in banks['data']:
    print(f"{bank['name']} - {bank['code']}")</code></pre>
                </div>
            </div>

            <h3>Response</h3>
            <div class="response">
                <h4>200 OK</h4>
                <pre><code class="language-json">{
    "status": "success",
    "message": "Banks retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Access Bank",
            "code": "044",
            "slug": "access-bank",
            "logo": "https://app.pointwave.ng/banks/access-bank.png",
            "active": true,
            "supports_transfer": true,
            "supports_account_lookup": true
        },
        {
            "id": 2,
            "name": "GTBank",
            "code": "058",
            "slug": "gtbank",
            "logo": "https://app.pointwave.ng/banks/gtbank.png",
            "active": true,
            "supports_transfer": true,
            "supports_account_lookup": true
        },
        {
            "id": 3,
            "name": "Zenith Bank",
            "code": "057",
            "slug": "zenith-bank",
            "logo": "https://app.pointwave.ng/banks/zenith-bank.png",
            "active": true,
            "supports_transfer": true,
            "supports_account_lookup": true
        },
        {
            "id": 4,
            "name": "First Bank",
            "code": "011",
            "slug": "first-bank",
            "logo": "https://app.pointwave.ng/banks/first-bank.png",
            "active": true,
            "supports_transfer": true,
            "supports_account_lookup": true
        }
        // ... more banks
    ],
    "meta": {
        "total": 24,
        "active": 24,
        "last_updated": "2026-02-18T10:00:00Z"
    }
}</code></pre>
            </div>

            <h3>Response Fields</h3>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>id</code></td>
                        <td>integer</td>
                        <td>Unique bank identifier</td>
                    </tr>
                    <tr>
                        <td><code>name</code></td>
                        <td>string</td>
                        <td>Full bank name</td>
                    </tr>
                    <tr>
                        <td><code>code</code></td>
                        <td>string</td>
                        <td>CBN bank code (use this for transfers)</td>
                    </tr>
                    <tr>
                        <td><code>slug</code></td>
                        <td>string</td>
                        <td>URL-friendly bank identifier</td>
                    </tr>
                    <tr>
                        <td><code>logo</code></td>
                        <td>string</td>
                        <td>Bank logo URL</td>
                    </tr>
                    <tr>
                        <td><code>active</code></td>
                        <td>boolean</td>
                        <td>Whether bank is currently active</td>
                    </tr>
                    <tr>
                        <td><code>supports_transfer</code></td>
                        <td>boolean</td>
                        <td>Whether bank supports transfers</td>
                    </tr>
                    <tr>
                        <td><code>supports_account_lookup</code></td>
                        <td>boolean</td>
                        <td>Whether bank supports account name lookup</td>
                    </tr>
                </tbody>
            </table>

            <!-- Common Banks -->
            <h2 id="common-banks">Common Nigerian Banks</h2>
            <p>Here are the most commonly used banks and their codes:</p>

            <table>
                <thead>
                    <tr>
                        <th>Bank Name</th>
                        <th>Bank Code</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Access Bank</td>
                        <td>044</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>GTBank</td>
                        <td>058</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Zenith Bank</td>
                        <td>057</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>First Bank</td>
                        <td>011</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>UBA</td>
                        <td>033</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Fidelity Bank</td>
                        <td>070</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Union Bank</td>
                        <td>032</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Stanbic IBTC</td>
                        <td>221</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Sterling Bank</td>
                        <td>232</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Wema Bank</td>
                        <td>035</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Polaris Bank</td>
                        <td>076</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Ecobank</td>
                        <td>050</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Kuda Bank</td>
                        <td>090267</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>Opay</td>
                        <td>999992</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>PalmPay</td>
                        <td>999991</td>
                        <td>✅ Active</td>
                    </tr>
                </tbody>
            </table>

            <!-- Usage Example -->
            <h2 id="usage-example">Usage Example</h2>
            <p>Here's a complete example of how to use the banks list in a real application:</p>

            <h3>HTML Select Dropdown</h3>
            <pre><code class="language-html">&lt;select id="bank-select" name="bank_code"&gt;
    &lt;option value=""&gt;Select Bank&lt;/option&gt;
    &lt;!-- Banks will be populated here --&gt;
&lt;/select&gt;</code></pre>

            <h3>JavaScript to Populate</h3>
            <pre><code class="language-javascript">// Fetch and populate banks
async function loadBanks() {
    try {
        const response = await fetch('https://app.pointwave.ng/api/v1/banks', {
            headers: {
                'Authorization': 'Bearer YOUR_SECRET_KEY',
                'x-api-key': 'YOUR_API_KEY',
                'x-business-id': 'YOUR_BUSINESS_ID'
            }
        });
        
        const data = await response.json();
        const select = document.getElementById('bank-select');
        
        data.data.forEach(bank => {
            if (bank.active) {
                const option = document.createElement('option');
                option.value = bank.code;
                option.textContent = bank.name;
                option.dataset.logo = bank.logo;
                select.appendChild(option);
            }
        });
        
        console.log('Banks loaded successfully');
    } catch (error) {
        console.error('Error loading banks:', error);
    }
}

// Call on page load
loadBanks();</code></pre>

            <div class="alert success">
                <strong>✅ Best Practice:</strong> Cache the banks list in localStorage or your database to reduce API
                calls. Update it once per day or when needed.
            </div>

            <!-- Caching Example -->
            <h2 id="caching">Caching Banks List</h2>
            <p>To improve performance, cache the banks list:</p>

            <pre><code class="language-javascript">// Cache banks in localStorage
async function getCachedBanks() {
    const cached = localStorage.getItem('banks_list');
    const cacheTime = localStorage.getItem('banks_cache_time');
    
    // Check if cache is valid (24 hours)
    if (cached && cacheTime) {
        const age = Date.now() - parseInt(cacheTime);
        if (age < 24 * 60 * 60 * 1000) {
            return JSON.parse(cached);
        }
    }
    
    // Fetch fresh data
    const response = await fetch('https://app.pointwave.ng/api/v1/banks', {
        headers: {
            'Authorization': 'Bearer YOUR_SECRET_KEY',
            'x-api-key': 'YOUR_API_KEY',
            'x-business-id': 'YOUR_BUSINESS_ID'
        }
    });
    
    const data = await response.json();
    
    // Cache the data
    localStorage.setItem('banks_list', JSON.stringify(data.data));
    localStorage.setItem('banks_cache_time', Date.now().toString());
    
    return data.data;
}</code></pre>

            <div class="alert warning">
                <strong>⚠️ Note:</strong> Always use the bank <code>code</code> field (not <code>id</code>) when making
                transfers or account lookups.
            </div>

            <!-- Error Handling -->
            <h2 id="errors">Error Handling</h2>
            <p>Handle potential errors when fetching banks:</p>

            <pre><code class="language-javascript">try {
    const banks = await getBanks();
    console.log('Banks loaded:', banks.length);
} catch (error) {
    if (error.response) {
        // API returned an error
        console.error('API Error:', error.response.data.message);
    } else if (error.request) {
        // No response received
        console.error('Network Error: No response from server');
    } else {
        // Other errors
        console.error('Error:', error.message);
    }
    
    // Fallback to cached data or show error message
    showErrorMessage('Unable to load banks. Please try again.');
}</code></pre>

            <div class="alert info">
                <strong>💡 Next Steps:</strong> Once you have the banks list, you can use it for <a
                    href="{{ route('docs.transfers') }}">transfers</a> or account verification.
            </div>
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
    <script>
        function showCode(id) {
            // Hide all code blocks
            document.querySelectorAll('.code-content').forEach(el => el.style.display = 'none');
            // Remove active class from all tabs
            document.querySelectorAll('.code-tab').forEach(el => el.classList.remove('active'));
            // Show selected code block
            document.getElementById(id).style.display = 'block';
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>

</html>