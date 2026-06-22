<?php
session_start();
include("db.php");

if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'Vendor' || !isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$vendorID = $_SESSION['UserID'];

// ---------------------------------------------------------
// VENDOR PROFILE UPDATER (Banner & Description)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_vendor_profile'])) {
    $desc = mysqli_real_escape_string($link, $_POST['vendor_description']);
    mysqli_query($link, "UPDATE vendor SET VendorDescription='$desc' WHERE UserID='$vendorID'");
    
    if (isset($_FILES['storeImage']) && $_FILES['storeImage']['error'] == 0) {
        $targetDir = "gambar/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); 
        $fileName = time() . "_" . basename($_FILES["storeImage"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES["storeImage"]["tmp_name"], $targetFilePath)) {
            mysqli_query($link, "UPDATE vendor SET VendorImage='$targetFilePath' WHERE UserID='$vendorID'");
        }
    }
    header("Location: vendorpage.php#edit-vendor");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_store_image'])) {
    if (isset($_FILES['storeImage']) && $_FILES['storeImage']['error'] == 0) {
        $targetDir = "gambar/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); 
        $safeFileName = preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES["storeImage"]["name"]));
        $fileName = time() . "_" . $safeFileName;
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES["storeImage"]["tmp_name"], $targetFilePath)) {
            mysqli_query($link, "UPDATE vendor SET VendorImage='$targetFilePath' WHERE UserID='$vendorID'");
            header("Location: vendorpage.php#dashboard");
            exit();
        }
    } else {
        echo "<script>alert('Upload failed!'); window.location.href='vendorpage.php';</script>";
        exit();
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today'; 
$customDate = isset($_GET['custom_date']) ? mysqli_real_escape_string($link, $_GET['custom_date']) : '';

$dateCond = ""; $reviewDateCond = "";
if ($filter == 'today') {
    $dateCond = "AND DATE(o.OrderDate) = CURDATE()";
    $reviewDateCond = "AND DATE(r.ReviewDate) = CURDATE()";
} elseif ($filter == 'week') {
    $dateCond = "AND YEARWEEK(o.OrderDate, 1) = YEARWEEK(CURDATE(), 1)";
    $reviewDateCond = "AND YEARWEEK(r.ReviewDate, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter == 'month') {
    $dateCond = "AND MONTH(o.OrderDate) = MONTH(CURDATE()) AND YEAR(o.OrderDate) = YEAR(CURDATE())";
    $reviewDateCond = "AND MONTH(r.ReviewDate) = MONTH(CURDATE()) AND YEAR(r.ReviewDate) = YEAR(CURDATE())";
} elseif ($filter == 'custom' && !empty($customDate)) {
    $dateCond = "AND DATE(o.OrderDate) = '$customDate'";
    $reviewDateCond = "AND DATE(r.ReviewDate) = '$customDate'";
}

if (isset($_GET['export_report'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="BiteGo_Sales_Report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Order ID', 'Date & Time', 'Order Type', 'Table No.', 'Customer Name', 'Status', 'Amount (RM)'));
    $exportQ = "SELECT o.OrderID, o.OrderDate, o.OrderType, o.TableNo, u.UserName, o.OrderStatus,
                       COALESCE((SELECT SUM(od.OrderedPrice) FROM order_detail od WHERE od.OrderID = o.OrderID), 0) AS OrderTotal
                FROM `order` o LEFT JOIN user u ON o.UserID = u.UserID
                WHERE o.VendorUserID='$vendorID' AND o.OrderStatus != 'Unpaid' $dateCond ORDER BY o.OrderDate DESC";
    $exportRes = mysqli_query($link, $exportQ);
    $grandTotal = 0;
    if($exportRes) {
        while($row = mysqli_fetch_assoc($exportRes)) {
            if ($row['OrderStatus'] == 'Done') { $grandTotal += $row['OrderTotal']; }
            fputcsv($output, array( $row['OrderID'], $row['OrderDate'], ucfirst($row['OrderType']), $row['TableNo'] ? $row['TableNo'] : 'N/A', $row['UserName'], $row['OrderStatus'], number_format($row['OrderTotal'], 2) ));
        }
    }
    fputcsv($output, array(''));
    fputcsv($output, array('', '', '', '', '', 'TOTAL COMPLETED SALES:', number_format($grandTotal, 2)));
    fclose($output); exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_sales'])) {
    $clearType = $_POST['clear_type'];
    $deleteCond = "WHERE VendorUserID='$vendorID'";
    if ($clearType == 'day') {
        $clearDate = mysqli_real_escape_string($link, $_POST['clear_date']);
        if (!empty($clearDate)) { $deleteCond .= " AND DATE(OrderDate)='$clearDate'"; }
    }
    $ordersToDelete = [];
    $getOrders = mysqli_query($link, "SELECT OrderID FROM `order` $deleteCond");
    while($ro = mysqli_fetch_assoc($getOrders)) { $ordersToDelete[] = "'" . $ro['OrderID'] . "'"; }
    if (!empty($ordersToDelete)) {
        $idString = implode(",", $ordersToDelete);
        mysqli_query($link, "DELETE FROM review WHERE OrderID IN ($idString)");
        mysqli_query($link, "DELETE FROM order_detail WHERE OrderID IN ($idString)");
        mysqli_query($link, "DELETE FROM payment WHERE OrderID IN ($idString)");
        mysqli_query($link, "DELETE FROM `order` WHERE OrderID IN ($idString)");

        $recalc = mysqli_query($link, "SELECT COALESCE(SUM(od.OrderedPrice), 0) as ts FROM order_detail od JOIN `order` o ON od.OrderID = o.OrderID WHERE o.VendorUserID='$vendorID' AND o.OrderStatus='Done'");
        $tsRow = mysqli_fetch_assoc($recalc);
        $newTS = $tsRow['ts'] ? $tsRow['ts'] : 0;
        mysqli_query($link, "UPDATE vendor SET VendorSales='$newTS' WHERE UserID='$vendorID'");
    }
    header("Location: vendorpage.php#analytics"); exit();
}

if (isset($_GET['api']) && $_GET['api'] == 'check_orders') {
    $res = mysqli_query($link, "SELECT COUNT(*) as cnt FROM `order` WHERE VendorUserID='$vendorID' AND OrderStatus='Pending'");
    $row = mysqli_fetch_assoc($res); echo json_encode(['count' => $row['cnt']]); exit();
}

if (isset($_POST['toggle_store'])) {
    $newStatus = mysqli_real_escape_string($link, $_POST['new_status']);
    mysqli_query($link, "UPDATE vendor SET StoreStatus='$newStatus' WHERE UserID='$vendorID'");
    header("Location: vendorpage.php#dashboard"); exit();
}
if (isset($_POST['update_tables'])) {
    $num = intval($_POST['total_tables']);
    mysqli_query($link, "UPDATE vendor SET TotalTables='$num' WHERE UserID='$vendorID'");
    header("Location: vendorpage.php#dashboard"); exit();
}
if (isset($_GET['action']) && $_GET['action'] == 'toggle_avail' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($link, $_GET['id']);
    mysqli_query($link, "UPDATE menu_item SET Availability = IF(Availability='Available', 'Sold Out', 'Available') WHERE ItemID='$id' AND UserID='$vendorID'");
    header("Location: vendorpage.php#manage"); exit();
}

// ---------------------------------------------------------
// LOCKED-DOWN MENU SUBMISSION LOGIC (Strict Dropdown)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manage_menu'])) {
    $itemName = mysqli_real_escape_string($link, $_POST['itemName']);
    $itemPrice = mysqli_real_escape_string($link, $_POST['itemPrice']);
    
    // We now securely grab the Category ID integer directly from the dropdown
    $catID = intval($_POST['category_id']); 
    
    $eta = mysqli_real_escape_string($link, $_POST['eta']);
    $desc = mysqli_real_escape_string($link, $_POST['description']);
    $imagePath = 'gambar/bitegologo.png'; $imageUpdateSql = "";

    if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
        $targetDir = "food/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); 
        $fileName = time() . "_" . basename($_FILES["itemImage"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["itemImage"]["tmp_name"], $targetFilePath)) {
            $imagePath = $targetFilePath; $imageUpdateSql = ", ItemImage='$imagePath'"; 
        }
    }

    if (!empty($_POST['itemID'])) {
        $id = mysqli_real_escape_string($link, $_POST['itemID']);
        mysqli_query($link, "UPDATE menu_item SET ItemName='$itemName', ItemPrice='$itemPrice', CategoryID='$catID', ETAMinutes='$eta', ItemDesc='$desc' $imageUpdateSql WHERE ItemID='$id' AND UserID='$vendorID'");
    } else {
        $id = uniqid('itm_');
        mysqli_query($link, "INSERT INTO menu_item (ItemID, UserID, ItemName, ItemPrice, CategoryID, Availability, ETAMinutes, ItemDesc, ItemImage) VALUES ('$id', '$vendorID', '$itemName', '$itemPrice', '$catID', 'Available', '$eta', '$desc', '$imagePath')");
    }
    header("Location: vendorpage.php#manage"); exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order_status'])) {
    $orderID = mysqli_real_escape_string($link, $_POST['orderID']);
    $status = mysqli_real_escape_string($link, $_POST['status']);
    
    $checkStatusQ = mysqli_query($link, "SELECT o.OrderStatus, o.UserID AS CustUserID, COALESCE((SELECT SUM(od.OrderedPrice) FROM order_detail od WHERE od.OrderID = o.OrderID), 0) AS OrderTotal FROM `order` o WHERE o.OrderID='$orderID' AND o.VendorUserID='$vendorID'");
    $orderData = mysqli_fetch_assoc($checkStatusQ);
    
    if ($orderData && $orderData['OrderStatus'] != 'Done') {
        mysqli_query($link, "UPDATE `order` SET OrderStatus='$status' WHERE OrderID='$orderID' AND VendorUserID='$vendorID'");
        if ($status == 'Done') {
            $total = $orderData['OrderTotal']; $custID = $orderData['CustUserID'];
            mysqli_query($link, "UPDATE vendor SET VendorSales = VendorSales + $total WHERE UserID='$vendorID'");
            if (strpos($custID, 'GUEST_') === false) { 
                $earnedPoints = floor($total);
                mysqli_query($link, "UPDATE customer SET Points = Points + $earnedPoints WHERE UserID='$custID'");
            }
        }
    }
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_order'])) {
    $orderID = mysqli_real_escape_string($link, $_POST['cancel_order_id']);
    $reason = mysqli_real_escape_string($link, $_POST['cancel_reason']);
    mysqli_query($link, "UPDATE `order` SET OrderStatus='Cancelled', CancelReason='$reason' WHERE OrderID='$orderID' AND VendorUserID='$vendorID'");
    header("Location: vendorpage.php#pos"); exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_menu' && isset($_GET['id'])) {
    $delID = mysqli_real_escape_string($link, $_GET['id']);
    mysqli_query($link, "DELETE FROM menu_item WHERE ItemID='$delID' AND UserID='$vendorID'");
    header("Location: vendorpage.php#manage"); exit();
}

// FETCH DETAILS
$vendorRes = mysqli_query($link, "SELECT u.UserName, v.StoreStatus, v.TotalTables, v.VendorImage, v.VendorDescription, v.VendorRating FROM vendor v JOIN user u ON u.UserID = v.UserID WHERE v.UserID='$vendorID'");
$vendorData = mysqli_fetch_assoc($vendorRes);
$vendorName = $vendorData ? $vendorData['UserName'] : 'Unknown Vendor';
$storeStatus = $vendorData['StoreStatus'] ?? 'Open'; $totalTables = $vendorData['TotalTables'] ?? 5;

$absPendingRes = mysqli_query($link, "SELECT COUNT(*) as cnt FROM `order` WHERE VendorUserID='$vendorID' AND OrderStatus='Pending'");
$absPendingRow = mysqli_fetch_assoc($absPendingRes); $absolutePendingCount = $absPendingRow ? $absPendingRow['cnt'] : 0;

$salesRes = mysqli_query($link, "SELECT COALESCE(SUM(od.OrderedPrice), 0) as TotalSales FROM order_detail od JOIN `order` o ON od.OrderID = o.OrderID WHERE o.VendorUserID='$vendorID' AND o.OrderStatus='Done' $dateCond");
$salesData = mysqli_fetch_assoc($salesRes); $filteredSales = $salesData['TotalSales'] ? $salesData['TotalSales'] : 0;

$pendingRes = mysqli_query($link, "SELECT COUNT(*) as cnt FROM `order` o WHERE o.VendorUserID='$vendorID' AND o.OrderStatus='Pending' $dateCond");
$pendingRow = mysqli_fetch_assoc($pendingRes); $filteredPending = $pendingRow ? $pendingRow['cnt'] : 0;

// CHARTS DATA
$trendLabels = []; $trendData = [];
$trendQ = "SELECT o.OrderID, COALESCE((SELECT SUM(od.OrderedPrice) FROM order_detail od WHERE od.OrderID = o.OrderID), 0) AS OrderTotal FROM `order` o WHERE o.VendorUserID='$vendorID' AND o.OrderStatus='Done' $dateCond ORDER BY o.OrderDate ASC LIMIT 30";
$trendRes = mysqli_query($link, $trendQ);
if($trendRes) { while($r = mysqli_fetch_assoc($trendRes)) { $trendLabels[] = "#" . substr($r['OrderID'], -5); $trendData[] = $r['OrderTotal']; } }
if(empty($trendLabels)) { $trendLabels = ['No Data']; $trendData = [0]; }

$topItemsLabels = []; $topItemsData = [];
$resTop = mysqli_query($link, "SELECT m.ItemName, SUM(od.Quantity) as qty FROM order_detail od JOIN `order` o ON od.OrderID=o.OrderID JOIN menu_item m ON od.ItemID=m.ItemID WHERE o.VendorUserID='$vendorID' AND o.OrderStatus='Done' $dateCond GROUP BY m.ItemID ORDER BY qty DESC LIMIT 5");
if($resTop) { while($row = mysqli_fetch_assoc($resTop)){ $topItemsLabels[] = $row['ItemName']; $topItemsData[] = $row['qty']; } }
if(empty($topItemsLabels)) { $topItemsLabels=['No Data']; $topItemsData=[0]; }

$catLabels = []; $catData = [];
$resCat = mysqli_query($link, "SELECT c.CategoryName AS Category, SUM(od.Quantity * m.ItemPrice) as rev FROM order_detail od JOIN `order` o ON od.OrderID=o.OrderID JOIN menu_item m ON od.ItemID=m.ItemID JOIN category c ON m.CategoryID=c.CategoryID WHERE o.VendorUserID='$vendorID' AND o.OrderStatus='Done' $dateCond GROUP BY c.CategoryID, c.CategoryName");
if($resCat) { while($row = mysqli_fetch_assoc($resCat)){ $catLabels[] = $row['Category']; $catData[] = $row['rev']; } }
if(empty($catLabels)) { $catLabels=['No Data']; $catData=[0]; }

$statusLabels = ['Done', 'Pending', 'Cooking', 'Ready', 'Cancelled']; $statusData = [0, 0, 0, 0, 0];
$resStat = mysqli_query($link, "SELECT OrderStatus, COUNT(*) as cnt FROM `order` o WHERE o.VendorUserID='$vendorID' AND o.OrderStatus != 'Unpaid' $dateCond GROUP BY o.OrderStatus");
if($resStat) { while($row = mysqli_fetch_assoc($resStat)){ $idx = array_search($row['OrderStatus'], $statusLabels); if($idx !== false) $statusData[$idx] = $row['cnt']; } }
if(array_sum($statusData) == 0) { $statusLabels=['No Data']; $statusData=[1]; } 

$menuItems = [];
$menuRes = mysqli_query($link, "SELECT m.*, c.CategoryName FROM menu_item m LEFT JOIN category c ON m.CategoryID = c.CategoryID WHERE m.UserID='$vendorID'");
if ($menuRes) while($m = mysqli_fetch_assoc($menuRes)) $menuItems[] = $m;

$activeOrders = [];

$orderRes = mysqli_query($link, "SELECT o.OrderID, o.OrderStatus, o.OrderType, o.TableNo, IFNULL(u.UserName, 'Guest Diner') AS CustName, COALESCE((SELECT SUM(od.OrderedPrice) FROM order_detail od WHERE od.OrderID = o.OrderID), 0) AS OrderTotal FROM `order` o LEFT JOIN user u ON o.UserID = u.UserID WHERE o.VendorUserID='$vendorID' AND o.OrderStatus != 'Unpaid' $dateCond ORDER BY FIELD(o.OrderStatus, 'Pending', 'Cooking', 'Ready', 'Done', 'Cancelled'), o.OrderDate DESC");
if ($orderRes) {
    while($o = mysqli_fetch_assoc($orderRes)) {
        $itemsStr = [];
        $detRes = mysqli_query($link, "SELECT od.Quantity, m.ItemName FROM order_detail od JOIN menu_item m ON od.ItemID = m.ItemID WHERE od.OrderID='".$o['OrderID']."'");
        if ($detRes) while($d = mysqli_fetch_assoc($detRes)) $itemsStr[] = floatval($d['Quantity']) . "x " . $d['ItemName'];
        $o['details'] = empty($itemsStr) ? 'No details' : implode(', ', $itemsStr);
        $activeOrders[] = $o;
    }
}

// ---------------------------------------------------------
// FETCH ALL CATEGORIES (IDs and Names) FOR THE DROPDOWN
// ---------------------------------------------------------
$allCategories = [];
$catQuery = mysqli_query($link, "SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName ASC");
if ($catQuery) { while($cRow = mysqli_fetch_assoc($catQuery)) { $allCategories[] = $cRow; } }

$reviews = [];
$revQuery = "SELECT r.OrderID, r.Rating, r.Comment, r.ReviewDate, u.UserName AS CustName FROM review r JOIN `order` o ON r.OrderID = o.OrderID JOIN customer c ON o.UserID = c.UserID JOIN user u ON c.UserID = u.UserID WHERE o.VendorUserID = '$vendorID' $reviewDateCond ORDER BY r.ReviewDate DESC";
$revRes = mysqli_query($link, $revQuery);
if ($revRes) {
    while ($r = mysqli_fetch_assoc($revRes)) {
        $foodItems = []; $foodQ = "SELECT od.Quantity, m.ItemName FROM order_detail od JOIN menu_item m ON od.ItemID = m.ItemID WHERE od.OrderID='".$r['OrderID']."'";
        $foodRes = mysqli_query($link, $foodQ);
        if ($foodRes) { while ($f = mysqli_fetch_assoc($foodRes)) { $foodItems[] = floatval($f['Quantity']) . "x " . $f['ItemName']; } }
        $r['food_string'] = empty($foodItems) ? 'Unknown Items' : implode(', ', $foodItems); $reviews[] = $r;
    }
}
$avgRating = 0;
if (count($reviews) > 0) {
    $totalStars = 0; foreach ($reviews as $rev) { $totalStars += $rev['Rating']; }
    $avgRating = round($totalStars / count($reviews), 1);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>BiteGo | Vendor Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background-color: #f4f6f8; color: #000; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #fff; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; padding: 30px 0; box-shadow: 2px 0 15px rgba(0,0,0,0.02); z-index: 10; }
        .sidebar-brand { font-size: 28px; font-weight: 900; text-align: center; letter-spacing: 2px; margin-bottom: 5px; }
        .sidebar-subtitle { font-size: 13px; font-weight: 700; text-align: center; color: #888; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 50px; }
        .nav-item { padding: 15px 40px; font-size: 16px; font-weight: 600; color: #666; text-decoration: none; transition: all 0.3s; border-left: 4px solid transparent; display: flex; align-items: center; cursor: pointer; }
        .nav-item:hover { color: #000; background-color: #fafafa; }
        .nav-item.active { color: #000; border-left-color: #000; background-color: #f8f9fa; }
        .logout-btn { margin-top: auto; color: #d9534f; }
        .logout-btn:hover { background-color: #fdf0f0; color: #c9302c; }
        
        .main-content { flex: 1; padding: 40px 60px; overflow-y: auto; position: relative;}
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .top-header h1 { margin: 0; font-size: 32px; font-weight: 800; }
        .vendor-profile { font-weight: bold; font-size: 14px; background: #fff; padding: 10px 20px; border-radius: 30px; border: 1px solid #eee; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        
        .filter-bar { background: #fff; padding: 15px 25px; border-radius: 12px; border: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .filter-left { display: flex; align-items: center; gap: 10px; }
        .filter-bar select, .filter-bar input { padding: 10px 15px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit; font-size: 14px; }
        
        .view-section { display: none; animation: fadeIn 0.4s ease forwards; }
        .view-section.active-view { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 40px; }
        .stat-card { background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #eee; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .stat-title { font-size: 14px; color: #888; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 10px; }
        .stat-number { font-size: 42px; font-weight: 900; color: #000; }
        
        .table-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .table-header-row h2 { margin: 0; font-size: 22px; }
        
        .search-bar { padding: 12px 20px; width: 350px; border-radius: 30px; border: 1px solid #ccc; font-size: 14px; outline: none; transition: 0.3s; }
        .search-bar:focus { border-color: #000; box-shadow: 0 0 10px rgba(0,0,0,0.1); }

        .btn-add { background-color: #000; color: #fff; padding: 12px 25px; border-radius: 8px; font-size: 14px; font-weight: bold; border: none; cursor: pointer; transition: 0.3s; }
        .btn-add:hover { background-color: #444; transform: scale(1.02); }
        
        .table-container { background-color: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #eee; overflow: hidden; margin-bottom: 40px;}
        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background-color: #fafafa; border-bottom: 2px solid #eee; }
        th { padding: 18px 25px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #888; }
        td { padding: 18px 25px; font-size: 15px; font-weight: 500; border-bottom: 1px solid #eee; }
        
        .badge { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .status-pending { background-color: #fff4e5; color: #b07d00; border: 1px solid #ffe3bc; }
        .status-cooking { background-color: #e3f2fd; color: #1976d2; border: 1px solid #bbdefb; }
        .status-ready   { background-color: #f1f8e9; color: #558b2f; border: 1px solid #dcedc8; }
        .status-soldout { background-color: #fdf0f0; color: #d9534f; border: 1px solid #f5c6cb; }
        
        .btn-pos { background: #000; color: #fff; border: none; padding: 10px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s;}
        .btn-pos:hover { opacity: 0.8; }
        .btn-export { background: #1976d2; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-export:hover { background: #115293; }

        .order-id-tag { font-family: monospace; font-weight: bold; background: #eee; padding: 4px 8px; border-radius: 4px; }
        
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 50px; }
        .chart-container { background-color: #ffffff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #eeeeee; }
        .chart-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; }
        .chart-header h3 { margin: 0; font-size: 18px; }

        .danger-zone { background: #fffcfc; border: 1px solid #f5c6cb; padding: 30px; border-radius: 15px; margin-bottom: 40px; }
        .danger-zone h3 { color: #c9302c; margin-top: 0; }
        .danger-btn { background: #d9534f; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .danger-btn:hover { background: #c9302c; }

        .review-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
        .review-card { background: #fff; border: 1px solid #eee; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); display: flex; flex-direction: column;}
        .review-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .review-stars { color: gold; font-size: 16px; }
        .review-datetime { font-size: 12px; color: #888; text-align: right; display: flex; flex-direction: column; }
        .review-datetime span.r-date { font-weight: bold; color: #000; font-size: 13px; }
        .review-food-tag { background: #f8f9fa; border: 1px solid #eee; padding: 10px 15px; border-radius: 8px; font-size: 13px; color: #555; margin-bottom: 15px; line-height: 1.5; display: flex; gap: 10px; align-items: flex-start; }
        .review-comment { font-size: 15px; color: #222; line-height: 1.6; font-style: italic; margin-bottom: 20px; flex: 1; }
        .review-footer { border-top: 1px dashed #eee; padding-top: 15px; display: flex; align-items: center; font-size: 13px; font-weight: bold; color: #000; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 40px; border-radius: 15px; width: 600px; }
        .form-row { display: flex; gap: 15px; }
        .form-group { flex: 1; margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-family: inherit;}
        .menu-preview-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; margin-right: 15px; vertical-align: middle;}
        
        .modal-buttons { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;}
        .modal-btn { padding: 12px 25px; font-size: 14px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: all 0.3s; border: none; }
        .btn-cancel { background-color: #eee; color: #000; }
        .btn-cancel:hover { background-color: #ddd; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">BiteGo.</div>
        <div class="sidebar-subtitle">Vendor Portal</div>
        <a href="#dashboard" class="nav-item active" onclick="switchView(event, 'dashboard', this)"><i class="fa-solid fa-chart-line" style="width:25px;"></i> Dashboard</a>
        <a href="#manage" class="nav-item" onclick="switchView(event, 'manage', this)"><i class="fa-solid fa-utensils" style="width:25px;"></i> Menu Editor</a>
        <a href="#pos" class="nav-item" onclick="switchView(event, 'pos', this)"><i class="fa-solid fa-bell-concierge" style="width:25px;"></i> Live Kitchen</a>
        <a href="#analytics" class="nav-item" onclick="switchView(event, 'analytics', this)"><i class="fa-solid fa-chart-pie" style="width:25px;"></i> Sales Analytics</a>
        
        <a href="#edit-vendor" class="nav-item" onclick="switchView(event, 'edit-vendor', this)"><i class="fa-solid fa-store" style="width:25px;"></i> Edit Vendor</a>
        
        <a href="#reviews" class="nav-item" onclick="switchView(event, 'reviews', this)"><i class="fa-solid fa-star" style="width:25px;"></i> Reviews</a>
        <a href="logout.php" class="nav-item logout-btn"><i class="fa-solid fa-right-from-bracket" style="width:25px;"></i> Log Out</a>
    </aside>

    <main class="main-content">
        
        <div class="top-header">
            <h1 id="pageTitle">Overview Dashboard</h1>
            <div style="display: flex; gap: 15px; align-items: center;">
                
                <button class="btn-pos" style="background:#1976d2;" onclick="openStoreImageModal()"><i class="fa-solid fa-image"></i> Quick Banner</button>

                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="toggle_store" value="1">
                    <input type="hidden" name="new_status" value="<?php echo $storeStatus == 'Open' ? 'Closed' : 'Open'; ?>">
                    <button type="submit" class="btn-pos" style="background-color: <?php echo $storeStatus == 'Open' ? '#2e7d32' : '#d9534f'; ?>; padding: 12px 20px; font-size:14px;">
                        <?php echo $storeStatus == 'Open' ? '<i class="fa-solid fa-door-open"></i> Store is Open' : '<i class="fa-solid fa-door-closed"></i> Store is Closed'; ?>
                    </button>
                </form>
                <div class="vendor-profile"><i class="fa-solid fa-store" style="color:#888; margin-right:5px;"></i> <?php echo htmlspecialchars($vendorName); ?></div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="filter-left">
                <i class="fa-solid fa-calendar-days" style="font-size:20px; color:#888;"></i>
                <form method="GET" style="display:flex; align-items:center; gap:10px;">
                    <label style="font-weight:bold; font-size:14px; margin:0;">Report Date Filter:</label>
                    <select name="filter" id="filterSelect" onchange="document.getElementById('customDateWrapper').style.display = this.value === 'custom' ? 'block' : 'none';">
                        <option value="today" <?php if($filter=='today') echo 'selected'; ?>>Today</option>
                        <option value="week" <?php if($filter=='week') echo 'selected'; ?>>This Week</option>
                        <option value="month" <?php if($filter=='month') echo 'selected'; ?>>This Month</option>
                        <option value="all" <?php if($filter=='all') echo 'selected'; ?>>All Time</option>
                        <option value="custom" <?php if($filter=='custom') echo 'selected'; ?>>Specific Date...</option>
                    </select>
                    
                    <div id="customDateWrapper" style="display: <?php echo $filter=='custom' ? 'block' : 'none'; ?>;">
                        <input type="date" name="custom_date" value="<?php echo htmlspecialchars($customDate); ?>">
                    </div>
                    
                    <button type="submit" class="btn-pos" style="background:#000;">Apply Filter</button>
                    <button type="submit" name="export_report" value="1" class="btn-export"><i class="fa-solid fa-file-csv"></i> Export Report</button>
                </form>
            </div>
        </div>

        <div id="dashboard" class="view-section active-view">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Filtered Sales Revenue</div>
                    <div class="stat-number">RM <?php echo number_format($filteredSales, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Filtered Pending Orders</div>
                    <div class="stat-number"><?php echo $filteredPending; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Table Configuration</div>
                    <form method="POST" style="display:flex; gap:10px; align-items: center;">
                        <input type="hidden" name="update_tables" value="1">
                        <input type="number" name="total_tables" value="<?php echo $totalTables; ?>" min="1" max="100" style="width:70px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-size:18px; font-weight:bold;">
                        <button type="submit" class="btn-pos" style="padding:10px 15px; background:#000;">Update</button>
                    </form>
                </div>
            </div>
            <div class="table-header-row"><h2>Revenue Per Order</h2></div>
            <div class="chart-container"><canvas id="salesTrendChart"></canvas></div>
        </div>

        <div id="manage" class="view-section">
            <div class="table-header-row">
                <h2>Restaurant Menu Items</h2>
                <button class="btn-add" onclick="openMenuModal()">+ Add New Menu</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($menuItems)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 30px;">Your menu is empty.</td></tr>
                        <?php else: ?>
                            <?php foreach($menuItems as $item): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($item['ItemImage']); ?>" class="menu-preview-img" onerror="this.src='gambar/bitegologo.png'">
                                    <b><?php echo htmlspecialchars($item['ItemName']); ?></b>
                                </td>
                                <td><?php echo htmlspecialchars($item['CategoryName']); ?></td>
                                
                                <td>
                                    <span class="badge <?php echo $item['Availability'] == 'Available' ? 'status-ready' : 'status-soldout'; ?>">
                                        <?php echo htmlspecialchars($item['Availability']); ?>
                                    </span>
                                </td>

                                <td>RM <?php echo number_format($item['ItemPrice'], 2); ?></td>
                                <td>
                                    <a href="?action=toggle_avail&id=<?php echo $item['ItemID']; ?>" style="color:#f57f17; font-weight:bold; margin-right: 15px;">
                                        Mark <?php echo $item['Availability'] == 'Available' ? 'Sold Out' : 'Available'; ?>
                                    </a>

                                    <a href="#" onclick="editMenuModal(
                                        '<?php echo $item['ItemID']; ?>', 
                                        '<?php echo htmlspecialchars(addslashes($item['ItemName'])); ?>', 
                                        '<?php echo $item['ItemPrice']; ?>', 
                                        '<?php echo $item['CategoryID']; ?>',  /* WE PASS THE ID DIRECTLY HERE NOW */
                                        '<?php echo htmlspecialchars(addslashes($item['ETAMinutes'])); ?>',
                                        '<?php echo htmlspecialchars(addslashes($item['ItemDesc'])); ?>'
                                    )" style="color:#000; font-weight:bold; margin-right: 15px;">Edit</a>
                                    
                                    <a href="?action=delete_menu&id=<?php echo $item['ItemID']; ?>" style="color:#d9534f; font-weight:bold;">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="pos" class="view-section">
            <div class="table-header-row">
                <h2>Active Orders Tracking</h2>
                <input type="text" id="orderSearch" class="search-bar" placeholder="Search Order ID or Customer Name..." onkeyup="searchOrders()">
            </div>
            
            <div class="table-container">
                <table id="posTable">
                    <thead><tr><th>Order No.</th><th>Type</th><th>Customer</th><th>Details</th><th>Status</th><th>Control</th></tr></thead>
                    <tbody>
                        <?php if(empty($activeOrders)): ?>
                            <tr class="no-records"><td colspan="6" style="text-align:center; padding: 30px;">No active orders right now. Good job!</td></tr>
                        <?php else: ?>
                            <?php foreach($activeOrders as $order): ?>
<?php 
                                    $status = $order['OrderStatus'];
                                    $statusClass = 'status-pending'; 
                                    $btnText = 'Start Cooking'; 
                                    $btnColor = '#000';
                                    $showButtons = true; // Added flag to hide buttons if done/cancelled

                                    if ($status == 'Cooking') { $statusClass = 'status-cooking'; $btnText = 'Food Ready'; $btnColor = '#1976d2'; } 
                                    else if ($status == 'Ready') { $statusClass = 'status-ready'; $btnText = 'Complete Order'; $btnColor = '#558b2f'; }
                                    else if ($status == 'Done') { $statusClass = 'status-ready'; $btnText = 'Completed'; $btnColor = '#888'; $showButtons = false; }
                                    else if ($status == 'Cancelled') { $statusClass = 'status-soldout'; $btnText = 'Cancelled'; $btnColor = '#d9534f'; $showButtons = false; }
                                ?>
                                <tr class="pos-row">
                                    <td><span class="order-id-tag"><?php echo $order['OrderID']; ?></span></td>
                                    
                                    <td>
                                        <?php if($order['OrderType'] == 'dinein'): ?>
                                            <span class="badge" style="background:#e3f2fd; color:#1976d2;"><i class="fa-solid fa-chair"></i> Tbl <?php echo $order['TableNo']; ?></span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#fff4e5; color:#f57f17;"><i class="fa-solid fa-bag-shopping"></i> Takeaway</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="cust-name"><b><?php echo htmlspecialchars($order['CustName']); ?></b></td>
                                    <td><?php echo htmlspecialchars($order['details']); ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                    <td>
                                        <?php if($showButtons): ?>
                                            <div style="display: flex; gap: 5px;">
                                                <button class="btn-pos" style="background-color: <?php echo $btnColor; ?>; min-width: 120px;" onclick="updatePOS('<?php echo $order['OrderID']; ?>', '<?php echo $status; ?>')"><?php echo $btnText; ?></button>
                                                <button class="btn-pos" style="background-color: #d9534f; padding: 10px;" onclick="openCancelModal('<?php echo $order['OrderID']; ?>')" title="Cancel Order"><i class="fa-solid fa-xmark"></i></button>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: <?php echo $btnColor; ?>; font-weight: bold; font-size: 14px;"><i class="fa-solid fa-check"></i> <?php echo $btnText; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="analytics" class="view-section">
            <div class="charts-grid">
                <div class="chart-container"><div class="chart-header"><h3>Total Revenue by Category</h3></div><canvas id="revenueCatChart"></canvas></div>
                <div class="chart-container"><div class="chart-header"><h3>Top 5 Selling Items</h3></div><canvas id="topItemsChart"></canvas></div>
                <div class="chart-container"><div class="chart-header"><h3>Order Fulfillment Status</h3></div><div style="width: 70%; margin: 0 auto;"><canvas id="statusChart"></canvas></div></div>
            </div>

            <div class="danger-zone">
                <h3><i class="fa-solid fa-triangle-exclamation"></i> Data Management (Danger Zone)</h3>
                <p style="color: #666; font-size:14px; margin-bottom: 20px;">Clearing sales data will permanently delete orders and receipts from your history. This does NOT affect your menu items.</p>
                <div style="display:flex; gap: 20px;">
                    <form method="POST" style="background: #fff; padding: 15px; border-radius:10px; border:1px solid #f5c6cb; flex:1;">
                        <input type="hidden" name="clear_sales" value="1">
                        <input type="hidden" name="clear_type" value="day">
                        <label style="font-size:12px; font-weight:bold; display:block; margin-bottom:10px;">Clear Sales For Specific Day:</label>
                        <div style="display:flex; gap:10px;">
                            <input type="date" name="clear_date" required style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; flex:1;">
                            <button type="button" onclick="openClearDataUI('day', this.form)" class="danger-btn">Clear Day</button>
                        </div>
                    </form>
                    <form method="POST" style="background: #fff; padding: 15px; border-radius:10px; border:1px solid #f5c6cb; flex:1; display:flex; flex-direction:column; justify-content:center;">
                        <input type="hidden" name="clear_sales" value="1">
                        <input type="hidden" name="clear_type" value="all">
                        <label style="font-size:12px; font-weight:bold; display:block; margin-bottom:10px;">Clear Entire Sales History:</label>
                        <button type="button" onclick="openClearDataUI('all', this.form)" class="danger-btn" style="width:100%;">Wipe Entire Sales Report</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="edit-vendor" class="view-section">
            <div class="table-header-row"><h2>Store Ratings Overview</h2></div>
            <div style="margin-bottom:40px;">
                <div class="stat-card" style="max-width: 400px;">
                    <div class="stat-title">Average Review Rating</div>
                    <div class="stat-number" style="color:#b07d00;"><i class="fa-solid fa-star"></i> <?php echo number_format($avgRating, 1); ?></div>
                </div>
            </div>

            <div class="table-header-row"><h2>Edit Vendor Profile</h2></div>
            <div style="background:#fff; padding:30px; border-radius:15px; border:1px solid #eee; margin-bottom:40px;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_vendor_profile" value="1">
                    
                    <div style="margin-bottom:20px;">
                        <label style="font-weight:bold; display:block; margin-bottom:10px;">Update Store Banner Image</label>
                        <img src="<?php echo htmlspecialchars($vendorData['VendorImage'] ?? 'gambar/bitegologo.png'); ?>" style="height:120px; border-radius:10px; margin-bottom:10px; border:1px solid #ccc; display:block; object-fit:cover;">
                        <input type="file" name="storeImage" accept="image/*" style="padding:10px; border:1px solid #ccc; border-radius:8px; width:100%; box-sizing:border-box;">
                    </div>

                    <div style="margin-bottom:20px;">
                        <label style="font-weight:bold; display:block; margin-bottom:10px;">Restaurant Description</label>
                        <textarea name="vendor_description" rows="4" style="width:100%; padding:15px; border:1px solid #ccc; border-radius:8px; font-family:inherit; box-sizing:border-box;" placeholder="Tell customers about your restaurant..."><?php echo htmlspecialchars($vendorData['VendorDescription'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn-pos" style="background:#000; padding:15px 30px; font-size:16px; margin-top:10px;">Save Profile Changes</button>
                </form>
            </div>
        </div>

        <div id="reviews" class="view-section">
            <div class="table-header-row">
                <h2>Customer Reviews</h2>
            </div>
            <?php if(empty($reviews)): ?>
                <div style="text-align: center; padding: 60px; background: #fff; border-radius: 15px; border: 1px solid #eee;">
                    <i class="fa-regular fa-comment-dots" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
                    <h3>No reviews for this period.</h3>
                </div>
            <?php else: ?>
                <div class="review-grid">
                    <?php foreach($reviews as $rev): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-stars">
                                    <?php 
                                        for($i=1; $i<=5; $i++) {
                                            if($i <= $rev['Rating']) echo '<i class="fa-solid fa-star"></i>';
                                            else echo '<i class="fa-regular fa-star" style="color:#ddd;"></i>';
                                        }
                                    ?>
                                </div>
                                <div class="review-datetime">
                                    <span class="r-date"><?php echo date("d M Y", strtotime($rev['ReviewDate'])); ?></span>
                                    <span><?php echo date("h:i A", strtotime($rev['ReviewDate'])); ?></span>
                                </div>
                            </div>
                            <div class="review-food-tag">
                                <i class="fa-solid fa-utensils"></i>
                                <div>
                                    <strong style="color:#000;">Order #<?php echo $rev['OrderID']; ?></strong><br>
                                    <?php echo htmlspecialchars($rev['food_string']); ?>
                                </div>
                            </div>
                            <div class="review-comment">
                                "<?php echo empty($rev['Comment']) ? "No written comment left by customer." : htmlspecialchars($rev['Comment']); ?>"
                            </div>
                            <div class="review-footer">
                                <i class="fa-regular fa-user" style="margin-right: 8px;"></i> 
                                Reviewed by: <?php echo htmlspecialchars($rev['CustName']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="vendorModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modalTitle">Add Menu Item</h2>
            <form method="POST" action="vendorpage.php" enctype="multipart/form-data">
                <input type="hidden" name="manage_menu" value="1">
                <input type="hidden" name="itemID" id="modalItemID" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="itemName" id="modalItemName" placeholder="e.g. Nasi Lemak Ayam" required>
                    </div>
                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" step="0.01" name="itemPrice" id="modalItemPrice" required>
                    </div>
                </div>

                <div class="form-row">
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" id="modalCategory" required style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-family: inherit;">
                            <option value="" disabled selected>-- Select Category --</option>
                            <?php foreach($allCategories as $cat): ?>
                                <option value="<?php echo $cat['CategoryID']; ?>"><?php echo htmlspecialchars($cat['CategoryName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Preparation ETA</label>
                        <input type="text" name="eta" id="modalETA" placeholder="e.g. 15 mins" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Food Description</label>
                    <textarea name="description" id="modalDescription" rows="2" placeholder="Describe ingredients..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Upload Food Photo</label>
                    <input type="file" name="itemImage" accept="image/*">
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="modal-btn btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="modal-btn" style="background:#000; color:#fff;">Save Menu</button>
                </div>
            </form>
        </div>
    </div>

    <div id="storeImageModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px;">
            <h2 style="font-size: 22px; margin-top:0;">Update Store Banner</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 25px;">Upload a new image to display on the BiteGo front page slider. Max file size: 2MB.</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_store_image" value="1">
                <div class="form-group">
                    <input type="file" name="storeImage" accept="image/*" required style="padding: 10px; border: 1px solid #ddd; width: 100%; box-sizing: border-box; border-radius: 8px;">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeStoreImageModal()" class="modal-btn btn-cancel" style="flex: 1; padding: 12px;">Cancel</button>
                    <button type="submit" class="modal-btn" style="flex: 1; padding: 12px; background: #000; color:#fff;">Upload Image</button>
                </div>
            </form>
        </div>
    </div>

    <div id="cancelOrderModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px;">
            <h2 style="font-size: 22px; margin-top:0; color:#d9534f;">Cancel Order</h2>
            <form method="POST">
                <input type="hidden" name="cancel_order" value="1">
                <input type="hidden" name="cancel_order_id" id="cancelOrderID">
                
                <div class="form-group">
                    <label>Reason for Cancellation (Customer will see this)</label>
                    <textarea name="cancel_reason" rows="3" placeholder="e.g. Sold out of chicken, Kitchen closing early..." required style="font-family: inherit; font-size:14px; border-color:#ccc; padding:12px;"></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="modal-btn btn-cancel" onclick="closeCancelModal()">Go Back</button>
                    <button type="submit" class="modal-btn" style="background:#d9534f; color:#fff;">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="clearDataModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: #d9534f; margin-bottom: 15px;"></i>
            <h2 style="font-size: 22px; margin-top:0;">Delete Sales Data?</h2>
            <p id="clearDataText" style="color: #666; font-size: 14px; margin-bottom: 25px;">Are you sure?</p>
            <div style="display: flex; gap: 10px;">
                <button onclick="closeClearDataUI()" class="modal-btn btn-cancel" style="flex: 1; padding: 12px;">Cancel</button>
                <button id="confirmClearBtn" class="modal-btn" style="flex: 1; padding: 12px; background: #d9534f; color:#fff; box-shadow: none;">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script>
        const trendLabels = <?php echo json_encode($trendLabels); ?>;
        const trendData = <?php echo json_encode($trendData); ?>;
        const topItemsLabels = <?php echo json_encode($topItemsLabels); ?>;
        const topItemsData = <?php echo json_encode($topItemsData); ?>;
        const catLabels = <?php echo json_encode($catLabels); ?>;
        const catData = <?php echo json_encode($catData); ?>;
        const statusLabels = <?php echo json_encode($statusLabels); ?>;
        const statusData = <?php echo json_encode($statusData); ?>;

        let chartsRendered = false;

        window.onload = function() {
            renderAllCharts();
            chartsRendered = true;
            if(window.location.hash) {
                let target = document.querySelector(`a[href="${window.location.hash}"]`);
                if(target) target.click();
            }
        };

        function switchView(event, viewId, element) {
            if(event) event.preventDefault();
            document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active-view'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById(viewId).classList.add('active-view');
            element.classList.add('active');
            
            document.getElementById('pageTitle').innerText = element.innerText.trim();
            window.history.replaceState(null, null, '#' + viewId);

            if((viewId === 'analytics' || viewId === 'dashboard') && !chartsRendered) {
                renderAllCharts(); chartsRendered = true;
            }
        }

        function searchOrders() {
            let input = document.getElementById('orderSearch').value.toLowerCase();
            let rows = document.querySelectorAll('.pos-row');
            
            rows.forEach(row => {
                let orderId = row.querySelector('.order-id-tag').innerText.toLowerCase();
                let custName = row.querySelector('.cust-name').innerText.toLowerCase();
                
                if (orderId.includes(input) || custName.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function updatePOS(orderId, currentStatus) {
            let nextStatus = '';
            if (currentStatus === 'Pending') nextStatus = 'Cooking';
            else if (currentStatus === 'Cooking') nextStatus = 'Ready';
            else if (currentStatus === 'Ready') nextStatus = 'Done';
            
            if (!nextStatus) return;

            fetch('vendorpage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 'update_order_status': '1', 'orderID': orderId, 'status': nextStatus })
            }).then(() => { window.location.reload(); });
        }

        function openCancelModal(orderId) {
            document.getElementById('cancelOrderID').value = orderId;
            document.getElementById('cancelOrderModal').style.display = 'flex';
        }
        function closeCancelModal() {
            document.getElementById('cancelOrderModal').style.display = 'none';
        }

        function openStoreImageModal() { document.getElementById('storeImageModal').style.display = 'flex'; }
        function closeStoreImageModal() { document.getElementById('storeImageModal').style.display = 'none'; }

        let formToSubmit = null;
        function openClearDataUI(type, form) {
            formToSubmit = form;
            if(type === 'day') {
                document.getElementById('clearDataText').innerText = "Are you sure you want to delete ALL orders for this specific date?";
            } else {
                document.getElementById('clearDataText').innerHTML = "<b>WARNING!</b> Are you sure you want to wipe ALL sales history? This cannot be undone!";
            }
            document.getElementById('clearDataModal').style.display = 'flex';
        }
        function closeClearDataUI() {
            document.getElementById('clearDataModal').style.display = 'none';
            formToSubmit = null;
        }
        document.getElementById('confirmClearBtn').addEventListener('click', function() {
            if(formToSubmit) formToSubmit.submit();
        });

        let currentPendingCount = <?php echo $absolutePendingCount; ?>;
        setInterval(() => {
            fetch('vendorpage.php?api=check_orders')
            .then(res => res.json())
            .then(data => {
                if(data.count > currentPendingCount) { window.location.reload(); } 
                else { currentPendingCount = data.count; }
            });
        }, 10000); 

        function openMenuModal() { 
            document.getElementById('modalTitle').innerText = "Add Menu Item";
            document.getElementById('modalItemID').value = "";
            document.getElementById('modalItemName').value = "";
            document.getElementById('modalItemPrice').value = "";
            document.getElementById('modalETA').value = "";
            document.getElementById('modalDescription').value = "";
            document.getElementById('modalCategory').value = ""; // Reset Dropdown

            document.getElementById('vendorModal').style.display = 'flex'; 
        }
        
        function editMenuModal(id, name, price, categoryId, eta, desc) {
            document.getElementById('modalTitle').innerText = "Edit Menu Item";
            document.getElementById('modalItemID').value = id;
            document.getElementById('modalItemName').value = name;
            document.getElementById('modalItemPrice').value = price;
            document.getElementById('modalETA').value = eta;
            document.getElementById('modalDescription').value = desc;
            
            // Set the strict dropdown value
            document.getElementById('modalCategory').value = categoryId;

            document.getElementById('vendorModal').style.display = 'flex'; 
        }

        function closeModal() { document.getElementById('vendorModal').style.display = 'none'; }
        
        function renderAllCharts() {
            Chart.defaults.font.family = "'Segoe UI', sans-serif";
            
            new Chart(document.getElementById('salesTrendChart'), { 
                type: 'line', 
                data: { 
                    labels: trendLabels, 
                    datasets: [{ 
                        label: 'Sales (RM)', 
                        data: trendData, 
                        borderColor: '#000', 
                        backgroundColor: 'rgba(0,0,0,0.05)', 
                        fill: true, 
                        tension: 0.3,
                        pointRadius: 6, 
                        pointBackgroundColor: '#000' 
                    }] 
                } 
            });

            new Chart(document.getElementById('revenueCatChart'), { 
                type: 'bar', 
                data: { labels: catLabels, datasets: [{ label: 'Revenue (RM)', data: catData, backgroundColor: '#000', borderRadius: 5 }] } 
            });
            
            new Chart(document.getElementById('topItemsChart'), { 
                type: 'bar', 
                data: { labels: topItemsLabels, datasets: [{ label: 'Total Units Sold', data: topItemsData, backgroundColor: '#555' }] }, 
                options: { indexAxis: 'y' } 
            });
            
            new Chart(document.getElementById('statusChart'), { 
                type: 'doughnut', 
                data: { 
                    labels: statusLabels, 
                    datasets: [{ 
                        data: statusData, 
                        backgroundColor: ['#558b2f', '#ffb300', '#1976d2', '#8e24aa', '#d9534f'] 
                    }] 
                } 
            });
        }
    </script>
</body>
</html>