<?php

$transactions = array(
    '0' => array(1,2,3,4),
    '1' =>array(1,2),
    array(2,3,4),
    array(2,3),
    array(1,2,4),
    array(3,4),
    array(2,4)
);
$products = array(1,2,3,4);
$candidates = array(array(1,2),array(1,3),array(1,4),array(2,3),array(2,4),array(3,4));
//$candidates = array(array(1,2,3,4));
//$candidates = array(array(2,3,4));

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

$candidates = generateCandidates($products);

$min_support = 2;
$final_support = array();

$candidates_i=0;
// iterate every itemset
foreach($candidates as $set)
{
    $support_candidates = array();
    $transaction_i = 0; // current transaction index
    // iterate every transaction
    foreach($transactions as $transaction)
    {
        $ignore=false;
        $count_set_all = count($set);
        $count_transaction_all = count($transaction);

        if( $count_transaction_all < $count_set_all )
        {
            // not enough products in current transaction
            $ignore=true;
        }

        if( ! $ignore )
        {
            foreach($set as $product)
            {
                if( in_array($product,$transaction))
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
        if($transaction_i==(count($transactions)-1)){
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
    $candidates_i++;
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