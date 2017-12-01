<?php
    /*
     * Reads the response returned from the store API and sets the values accordingly.
     */
    header("Content-Type:text/html; charset=utf-8");
    if (isset($_REQUEST['storeid']) && $_REQUEST['storeid'] !== '') {
        $storeId   = $_REQUEST['storeid'];
        $storeName = $_REQUEST['storename'];
        $storeAddress = $_REQUEST['address'];
    } else if (isset($_REQUEST['cvsspot']) && $_REQUEST['cvsspot'] !== '') {
        $storeId   = $_REQUEST['cvsspot'];
        $storeName = $_REQUEST['name'];
        $storeAddress = $_REQUEST['addr'];
    }
?>

<script type="text/javascript">
    window.opener.document.getElementById("storeid").value = "<?php echo $storeId;?>";
    window.opener.document.getElementById("store_id").value = "<?php echo $storeId;?>";
    window.opener.document.getElementById("store_name").value = "<?php echo $storeName;?>";
    window.opener.document.getElementById("store_name_field").style.display = 'block';
    window.opener.document.getElementById("store_address").value = "<?php echo $storeAddress;?>";
    window.opener.document.getElementById("store_address_field").style.display = 'block';
    window.close();
</script>
