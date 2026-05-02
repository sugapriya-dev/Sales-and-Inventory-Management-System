
<?php

///In Supplier Ledger,total bill amount calculation

function purchase($supid)
{
    include "db.php";

    $purchase_query="SELECT COUNT(supplier_id) as count ,sum(grand_total) as total from purchase WHERE supplier_id=$supid";   
    
    $pur = $conn->prepare($purchase_query);
    $pur->execute();
    $purchases = $pur->get_result();

    $entry = $purchases->fetch_assoc();
    
    return $entry;
}

///In Supplier Ledger,total Payment calculation

function sup_payment($supid)
{
    include "db.php";

    $purchase_query="SELECT sum(dr.total) as tot from  (SELECT ABS(sum(amt)) as total from cash_in_hand where payee=$supid UNION SELECT ABS(sum(amt)) as total from bank_ledger where payee=$supid ) as dr";   
    
    $pur = $conn->prepare($purchase_query);
    $pur->execute();
    $purchases = $pur->get_result();

    $entry = $purchases->fetch_assoc();
    
    return $entry['tot'];
}

///In Supplier Ledger,total Balance calculation

function sup_outstanding($supid)
{
    $total_purchase=purchase($supid);
    $total_payment=sup_payment($supid);
    
    $outstand=$total_purchase['total']-$total_payment;

    return $outstand;
}

///In Customer Ledger,total bill amount calculation


function cust_total($cusid)
{

    include "db.php";
     $sales_query="SELECT COUNT(customer_id) as count ,sum(grand_total) as total from sales WHERE customer_id=$cusid";

     $res = $conn->prepare($sales_query);
     
     $res->execute();
     $sales = $res->get_result();
     $entry = $sales->fetch_assoc();
    
    return $entry;


}

///In Customer Ledger,total payment calculation


function cust_payment($cusid)
{
    include "db.php";
    $sales_query="SELECT sum(dr.total) as tot from  (SELECT ABS(sum(amt)) as total from cash_in_hand where payee=$cusid UNION SELECT ABS(sum(amt)) as total from bank_ledger where payee=$cusid ) as dr";

    $res=$conn->prepare($sales_query);
    $res->execute();
    $sales=$res->get_result();
    $entry=$sales->fetch_assoc();

    return $entry['tot'];
}


///In Customer Ledger,total Balance calculation

function cust_outstanding($cusid)
{
    $total_sales=cust_total($cusid);
    $total_payment=cust_payment($cusid);
    
    $outstand=$total_sales['total']-$total_payment;

    return $outstand;
}

?>


