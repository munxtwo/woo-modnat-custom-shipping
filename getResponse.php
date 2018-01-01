<?php
    /*
     * Reads the response returned from the store API and sets the values accordingly.
     */
    header("Content-Type:text/html; charset=utf-8");
    if (isset($_REQUEST['storeid']) && $_REQUEST['storeid'] !== '') {
        $responseType = "711";
        $storeId   = $_REQUEST['storeid'];
        $storeName = $_REQUEST['storename'];
        $storeAddress = $_REQUEST['address'];
    } else if (isset($_REQUEST['cvsspot']) && $_REQUEST['cvsspot'] !== '') {
        $responseType = "CVS";
        $storeId   = $_REQUEST['cvsspot'];
        $storeName = $_REQUEST['name'];
        $storeAddress = $_REQUEST['addr'];
    }
?>

<script type="text/javascript">
    var responseType = "<?php echo $responseType;?>";
    if (responseType === '711' && window.opener.document.getElementById("storeid") != null) {
        window.opener.document.getElementById("storeid").value = "<?php echo $storeId;?>";
    } else if (responseType === 'CVS') {
        var action = window.opener.document.getElementById("mapFormId").action;
        action.replace(/cvsspot*[&]?/, 'cvsspot=' + "<?php echo $storeId;?>");
    }
    window.opener.document.getElementById("store_id").value = "<?php echo $storeId;?>";
    window.opener.document.getElementById("store_name").value = "<?php echo $storeName;?>";
    window.opener.document.getElementById("store_name_field").style.display = 'block';
    window.opener.document.getElementById("store_address").value = "<?php echo $storeAddress;?>";
    window.opener.document.getElementById("store_address_field").style.display = 'block';
    window.close();
</script>
