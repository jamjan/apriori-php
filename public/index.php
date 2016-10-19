<?php

require '../vendor/autoload.php';

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql;
use Zend\Db\ResultSet\HydratingResultSet;

$min_support = 2;
$tr = null;
$candidates_i=0;
$final_support = array();

$adapter = new Adapter([
    'driver'   => 'pdo_sqlite',
    'database' => '/var/www/data/apriori.sqlite'
]);

$productsTable = new TableGateway('products', $adapter, null,new HydratingResultSet());
$products = $productsTable->select();

$transactionsTable = new TableGateway('transactions', $adapter, null,new HydratingResultSet());
$transactions = $transactionsTable->select();

// read and aggregate transactions-details for every transaction
foreach($transactions as $transaction) {
    $current_transaction = $transaction->getArrayCopy();
    if(null!==$current_transaction) {
        $transactionsDetailsTable = new TableGateway('transactions_details', $adapter, null,new HydratingResultSet());
        $where = new Where();
        $where->like('transaction_id', $current_transaction['transaction_id']);
        $transaction_details = $transactionsDetailsTable->select($where);
        foreach($transaction_details as $t) {
            $tr[$current_transaction['transaction_id']][] = $t->getArrayCopy();
        }
    }
}

if(null!==$tr) {

    $products_data = $products->toArray();
    $p_cand = [];
    // extract products ids in order to generate candidates
    foreach($products_data as $product) {

        // LIMIT to 5

        if($product['id']>5) {
            continue;
        }
        $p_cand[] = $product['id'];
    }

    $candidates = generateCandidates($p_cand);
#
    echo '<pre>';

    foreach($candidates as $set)
    {
        $support_candidates = array();
        $transaction_i = 0; // current transaction index
        // iterate every transaction
        foreach($tr as $transaction)
        {
            foreach($transaction as $t) {
                $tr_ids[] = $t['item_id'];
            }
            $ignore=false;
            $count_set_all = count($set);
            $count_transaction_all = count($transaction);

            if( $count_transaction_all < $count_set_all )
            {
                // not enough products in current transaction
            //    $ignore=true;
            }

            if( ! $ignore )
            {
                foreach($set as $product)
                {
                    if( in_array($product,$tr_ids))
                    {
                        if(isset($support_candidates[$transaction_i][$product])){
                            $support_candidates[$transaction_i][$product]++;
                        } else {
                            $support_candidates[$transaction_i][$product] = 1;
                        }
                    }
                    // last item
                }
            }
            // last product in transaction?
            if($transaction_i==(count($tr)-1)){
                $mid_support=0;
                foreach($support_candidates as $transaction_nb=>$support_temp_transaction)
                {
                    if(count($support_temp_transaction) == count($set))
                    {
                        // all items present in current transaction
                        $mid_support++;
                    }
                } // foreach
                // keep only those above the minimal support
                if($mid_support>=$min_support){
                    $final_support[$candidates_i]=$mid_support;
                }
            } // endif
            $transaction_i++;
        } // endforeach
//    echo "<hr />";
    } // endforeach
    echo "<pre>";
    foreach($final_support as $trans_id => $sup){
        foreach($candidates[$trans_id] as $cand)
        {
            echo $cand . ', ';
        }
        echo ' = ' . $sup;
        echo '<br />';
    }
    var_dump($final_support);
    echo '</pre>';
}


//updating... $data, $where
//$sampleTable->update(array(
//    'name' => 'iman'
//), array('id'=>1));
//
////inserting...
//$sampleTable->insert(array(
//    'name' => 'Dony'
//));

// generate pairs for products
function generateCandidates($products=array()){
    $products = array_unique($products);
    $count_products = count($products);
    $total_combinations = pow(2, $count_products);
    $output = array();

    for ($i = 0; $i < $total_combinations; $i++) {
        $output_local = array();
        //For each combination check if each bit is set
        for ($j = 0; $j < $count_products; $j++) {
            //Is bit $j set in $i?
            if (pow(2, $j) & $i)
            {
                $output_local[$j] = $products[$j];

            }
        }
        $output[]=$output_local;
    }
    return $output;
}