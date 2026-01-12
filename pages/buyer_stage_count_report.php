<?php
/**
 * Buyer Stage Count Report - Standalone PHP Script
 * 
 * Fast query to get each stage of data count for buyer
 * Optimized for performance with proper indexing
 * 
 * Usage: php buyer_stage_count_report.php
 * Or access via web browser if placed in web directory
 */

// Database Configuration - Update these values
define('DB_HOST', 'localhost');
define('DB_NAME', 'rc_vendors_db'); // Update with your database name
define('DB_USER', 'root');      // Update with your database username
define('DB_PASS', '');       // Update with your database password
define('DB_CHARSET', 'utf8mb4');

// Optional: Date range filter (leave null for all data)
$dateFrom = null; // Format: '2024-01-01' or null for all
$dateTo = null;   // Format: '2024-12-31' or null for all

// Optional: Plant filter
$plantFilter = null; // e.g., 'PLANT001' or null for all

// Optional: Order type filter
$orderTypeFilter = 'purchaseOrder'; // 'purchaseOrder', 'stn', or null for all

/**
 * Create database connection
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Fast query to get buyer stage counts
 * Uses optimized SQL with proper indexing hints
 */
function getBuyerStageCounts($pdo, $dateFrom = null, $dateTo = null, $plantFilter = null, $orderTypeFilter = null) {
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    // Filter by buyer name (exclude null/empty)
    $whereConditions[] = "se.buyerName IS NOT NULL AND se.buyerName != ''";
    
    // Date filter
    if ($dateFrom !== null) {
        $whereConditions[] = "se.createdAt >= :dateFrom";
        $params[':dateFrom'] = $dateFrom;
    }
    if ($dateTo !== null) {
        $whereConditions[] = "se.createdAt <= :dateTo";
        $params[':dateTo'] = $dateTo . ' 23:59:59';
    }
    
    // Plant filter
    if ($plantFilter !== null) {
        $whereConditions[] = "se.plant = :plant";
        $params[':plant'] = $plantFilter;
    }
    
    // Order type filter
    if ($orderTypeFilter !== null) {
        $whereConditions[] = "se.orderType = :orderType";
        $params[':orderType'] = $orderTypeFilter;
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    /**
     * Optimized Query - Single query with conditional aggregation
     * This is faster than multiple queries as it scans the table only once
     */
    $sql = "
        SELECT 
            se.buyerName AS buyer_name,
            se.buyer_id,
            
            -- Stage Counts
            COUNT(CASE WHEN se.status = 'Completed' THEN 1 END) AS stage_completed_count,
            COUNT(CASE WHEN se.status = 'docking' OR se.status = 'Docking' THEN 1 END) AS stage_docking_count,
            COUNT(CASE WHEN se.status = 'GRN' OR se.status = 'grn' THEN 1 END) AS stage_grn_count,
            COUNT(CASE WHEN se.status = 'parking' OR se.status = 'Parking' THEN 1 END) AS stage_parking_count,
            COUNT(CASE WHEN se.status IS NULL OR se.status = '' THEN 1 END) AS stage_pending_count,
            COUNT(CASE WHEN se.status NOT IN ('Completed', 'docking', 'Docking', 'GRN', 'grn', 'parking', 'Parking') 
                       AND (se.status IS NOT NULL AND se.status != '') THEN 1 END) AS stage_other_count,
            COUNT(*) AS stage_total_count,
            
            -- Bundle Counts by Stage
            COALESCE(SUM(CASE WHEN se.status = 'Completed' THEN se.noOfBundle ELSE 0 END), 0) AS completed_bundles,
            COALESCE(SUM(CASE WHEN se.status = 'docking' OR se.status = 'Docking' THEN se.noOfBundle ELSE 0 END), 0) AS docking_bundles,
            COALESCE(SUM(CASE WHEN se.status = 'GRN' OR se.status = 'grn' THEN se.noOfBundle ELSE 0 END), 0) AS grn_bundles,
            COALESCE(SUM(CASE WHEN se.status = 'parking' OR se.status = 'Parking' THEN se.noOfBundle ELSE 0 END), 0) AS parking_bundles,
            COALESCE(SUM(CASE WHEN se.status IS NULL OR se.status = '' THEN se.noOfBundle ELSE 0 END), 0) AS pending_bundles,
            COALESCE(SUM(se.noOfBundle), 0) AS total_bundles,
            
            -- Invoice Quantity by Stage
            COALESCE(SUM(CASE WHEN se.status = 'Completed' THEN se.invoiceQty ELSE 0 END), 0) AS completed_quantity,
            COALESCE(SUM(CASE WHEN se.status = 'docking' OR se.status = 'Docking' THEN se.invoiceQty ELSE 0 END), 0) AS docking_quantity,
            COALESCE(SUM(CASE WHEN se.status = 'GRN' OR se.status = 'grn' THEN se.invoiceQty ELSE 0 END), 0) AS grn_quantity,
            COALESCE(SUM(CASE WHEN se.status = 'parking' OR se.status = 'Parking' THEN se.invoiceQty ELSE 0 END), 0) AS parking_quantity,
            COALESCE(SUM(CASE WHEN se.status IS NULL OR se.status = '' THEN se.invoiceQty ELSE 0 END), 0) AS pending_quantity,
            COALESCE(SUM(se.invoiceQty), 0) AS total_quantity,
            
            -- Invoice Amount by Stage
            COALESCE(SUM(CASE WHEN se.status = 'Completed' THEN se.invoiceAmount ELSE 0 END), 0) AS completed_amount,
            COALESCE(SUM(CASE WHEN se.status = 'docking' OR se.status = 'Docking' THEN se.invoiceAmount ELSE 0 END), 0) AS docking_amount,
            COALESCE(SUM(CASE WHEN se.status = 'GRN' OR se.status = 'grn' THEN se.invoiceAmount ELSE 0 END), 0) AS grn_amount,
            COALESCE(SUM(CASE WHEN se.status = 'parking' OR se.status = 'Parking' THEN se.invoiceAmount ELSE 0 END), 0) AS parking_amount,
            COALESCE(SUM(CASE WHEN se.status IS NULL OR se.status = '' THEN se.invoiceAmount ELSE 0 END), 0) AS pending_amount,
            COALESCE(SUM(se.invoiceAmount), 0) AS total_amount,
            
            -- SOR Related Counts
            COUNT(CASE WHEN se.sorNo IS NOT NULL AND se.sorNo != '' THEN 1 END) AS sor_related_count,
            COUNT(CASE WHEN se.sorNo IS NULL OR se.sorNo = '' THEN 1 END) AS sor_not_related_count,
            
            -- Sync Status
            COUNT(CASE WHEN se.issynced = 1 OR se.issynced = '1' THEN 1 END) AS synced_count,
            COUNT(CASE WHEN se.issynced IS NULL OR se.issynced = 0 OR se.issynced = '0' THEN 1 END) AS not_synced_count,
            
            -- Date Range Info
            MIN(se.createdAt) AS first_entry_date,
            MAX(se.createdAt) AS last_entry_date
            
        FROM sir_entries se
        {$whereClause}
        GROUP BY se.buyerName, se.buyer_id
        ORDER BY se.buyerName ASC
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Query execution failed: " . $e->getMessage());
    }
}

/**
 * Get summary totals across all buyers
 */
function getSummaryTotals($pdo, $dateFrom = null, $dateTo = null, $plantFilter = null, $orderTypeFilter = null) {
    $whereConditions = [];
    $params = [];
    
    $whereConditions[] = "se.buyerName IS NOT NULL AND se.buyerName != ''";
    
    if ($dateFrom !== null) {
        $whereConditions[] = "se.createdAt >= :dateFrom";
        $params[':dateFrom'] = $dateFrom;
    }
    if ($dateTo !== null) {
        $whereConditions[] = "se.createdAt <= :dateTo";
        $params[':dateTo'] = $dateTo . ' 23:59:59';
    }
    if ($plantFilter !== null) {
        $whereConditions[] = "se.plant = :plant";
        $params[':plant'] = $plantFilter;
    }
    if ($orderTypeFilter !== null) {
        $whereConditions[] = "se.orderType = :orderType";
        $params[':orderType'] = $orderTypeFilter;
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    $sql = "
        SELECT 
            COUNT(DISTINCT se.buyerName) AS total_buyers,
            COUNT(*) AS total_entries,
            COUNT(CASE WHEN se.status = 'Completed' THEN 1 END) AS total_completed,
            COUNT(CASE WHEN se.status = 'docking' OR se.status = 'Docking' THEN 1 END) AS total_docking,
            COUNT(CASE WHEN se.status = 'GRN' OR se.status = 'grn' THEN 1 END) AS total_grn,
            COALESCE(SUM(se.noOfBundle), 0) AS total_bundles,
            COALESCE(SUM(se.invoiceQty), 0) AS total_quantity,
            COALESCE(SUM(se.invoiceAmount), 0) AS total_amount
        FROM sir_entries se
        {$whereClause}
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        die("Summary query failed: " . $e->getMessage());
    }
}

/**
 * Output results as JSON
 */
function outputJSON($data, $summary = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'summary' => $summary,
        'data' => $data,
        'count' => count($data)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Output results as HTML table
 */
function outputHTML($data, $summary = null) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Buyer Stage Count Report</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1 { color: #333; }
            .summary { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .summary h2 { margin-top: 0; color: #2c3e50; }
            .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
            .summary-item { background: white; padding: 10px; border-radius: 4px; }
            .summary-item strong { color: #3498db; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
            th { background: #34495e; color: white; padding: 12px; text-align: left; position: sticky; top: 0; }
            td { padding: 10px; border-bottom: 1px solid #ddd; }
            tr:hover { background: #f9f9f9; }
            .number { text-align: right; }
            .stage-completed { color: #27ae60; font-weight: bold; }
            .stage-docking { color: #f39c12; font-weight: bold; }
            .stage-grn { color: #3498db; font-weight: bold; }
            .stage-parking { color: #95a5a6; }
            .stage-pending { color: #e74c3c; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Buyer Stage Count Report</h1>
            
            <?php if ($summary): ?>
            <div class="summary">
                <h2>Summary</h2>
                <div class="summary-grid">
                    <div class="summary-item"><strong>Total Buyers:</strong> <?= $summary['total_buyers'] ?></div>
                    <div class="summary-item"><strong>Total Entries:</strong> <?= number_format($summary['total_entries']) ?></div>
                    <div class="summary-item"><strong>Completed:</strong> <span class="stage-completed"><?= number_format($summary['total_completed']) ?></span></div>
                    <div class="summary-item"><strong>Docking:</strong> <span class="stage-docking"><?= number_format($summary['total_docking']) ?></span></div>
                    <div class="summary-item"><strong>GRN:</strong> <span class="stage-grn"><?= number_format($summary['total_grn']) ?></span></div>
                    <div class="summary-item"><strong>Total Bundles:</strong> <?= number_format($summary['total_bundles']) ?></div>
                    <div class="summary-item"><strong>Total Quantity:</strong> <?= number_format($summary['total_quantity']) ?></div>
                    <div class="summary-item"><strong>Total Amount:</strong> ₹<?= number_format($summary['total_amount'], 2) ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Buyer Name</th>
                        <th class="number">Completed</th>
                        <th class="number">Docking</th>
                        <th class="number">GRN</th>
                        <th class="number">Parking</th>
                        <th class="number">Pending</th>
                        <th class="number">Other</th>
                        <th class="number">Total</th>
                        <th class="number">Total Bundles</th>
                        <th class="number">Total Qty</th>
                        <th class="number">Total Amount</th>
                        <th class="number">SOR Related</th>
                        <th class="number">SOR Not Related</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['buyer_name']) ?></strong></td>
                        <td class="number stage-completed"><?= number_format($row['stage_completed_count']) ?></td>
                        <td class="number stage-docking"><?= number_format($row['stage_docking_count']) ?></td>
                        <td class="number stage-grn"><?= number_format($row['stage_grn_count']) ?></td>
                        <td class="number stage-parking"><?= number_format($row['stage_parking_count']) ?></td>
                        <td class="number stage-pending"><?= number_format($row['stage_pending_count']) ?></td>
                        <td class="number"><?= number_format($row['stage_other_count']) ?></td>
                        <td class="number"><strong><?= number_format($row['stage_total_count']) ?></strong></td>
                        <td class="number"><?= number_format($row['total_bundles']) ?></td>
                        <td class="number"><?= number_format($row['total_quantity']) ?></td>
                        <td class="number">₹<?= number_format($row['total_amount'], 2) ?></td>
                        <td class="number"><?= number_format($row['sor_related_count']) ?></td>
                        <td class="number"><?= number_format($row['sor_not_related_count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 20px; color: #7f8c8d; font-size: 11px;">
                Generated: <?= date('Y-m-d H:i:s') ?> | Total Records: <?= count($data) ?>
            </p>
        </div>
    </body>
    </html>
    <?php
}

// Main execution
try {
    $pdo = getDBConnection();
    
    // Get buyer stage counts
    $buyerData = getBuyerStageCounts($pdo, $dateFrom, $dateTo, $plantFilter, $orderTypeFilter);
    
    // Get summary totals
    $summary = getSummaryTotals($pdo, $dateFrom, $dateTo, $plantFilter, $orderTypeFilter);
    
    // Determine output format
    $format = isset($_GET['format']) ? $_GET['format'] : (php_sapi_name() === 'cli' ? 'json' : 'html');
    
    if ($format === 'json') {
        outputJSON($buyerData, $summary);
    } else {
        outputHTML($buyerData, $summary);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

