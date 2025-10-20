SELECT date_sold, SUM(cash_gross) AS cash_gross, SUM(debtor_gross) AS debtor_gross, SUM(cash_reversed) AS cash_reversed, SUM(debtor_reversed) AS debtor_reversed, SUM(expenses) AS expenses, SUM(cash_profit) AS cash_profit, SUM(gross_profit) AS gross_profit, SUM(reversed_profit) AS reversed_profit, SUM(reversed_profit_2) AS reversed_profit_2
    
    FROM
    
    (
    
    SELECT a.date_sold AS date_sold,
            IFNULL(SUM(cash_sales.gross_sales), 0) AS cash_gross,
            IFNULL(SUM(debtor_sales.gross_sales), 0) AS debtor_gross,
            IFNULL(SUM(reversed_cash.sales_reversed), 0) AS cash_reversed,
            IFNULL(SUM(reversed_debtor.sales_reversed), 0) AS debtor_reversed,
            IFNULL(SUM(expenses.expense), 0) AS expenses,
            IFNULL(SUM(cash_sales.gross_profit), 0) AS cash_profit,
            IFNULL(SUM(debtor_sales.gross_profit), 0) AS gross_profit,
            IFNULL(SUM(reversed_cash.reversed_profit), 0) AS reversed_profit,
            IFNULL(SUM(reversed_debtor.reversed_profit), 0) AS reversed_profit_2
            
            
            FROM
            
            (SELECT date_sold
            FROM (
                SELECT DATE_FORMAT(ss.date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock ss WHERE DATE_FORMAT(ss.date_sold, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(ss.date_sold, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') AS date_sold FROM sold_stock_transfers sst WHERE DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(sst.date_transfered, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock_debtors ssd WHERE DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(ssd.date_sold, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') AS date_sold FROM expenses ex WHERE DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(ex.date_incurred, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(si.date_created, '%Y-%m-%d') AS date_sold FROM supplier_invoices si WHERE DATE_FORMAT(si.date_created, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(si.date_created, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(si.date_created, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(sp.date_added, '%Y-%m-%d') AS date_sold FROM stock_positive sp WHERE DATE_FORMAT(sp.date_added, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sp.date_added, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(sp.date_added, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(sn.date_removed, '%Y-%m-%d') AS date_sold FROM stock_negative sn WHERE DATE_FORMAT(sn.date_removed, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sn.date_removed, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(sn.date_removed, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(sr.date_returned, '%Y-%m-%d') AS date_sold FROM stock_returns sr WHERE DATE_FORMAT(sr.date_returned, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sr.date_returned, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(sr.date_returned, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(dp.date_of_payment, '%Y-%m-%d') AS date_sold FROM debtors_payments dp WHERE DATE_FORMAT(dp.date_of_payment, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(dp.date_of_payment, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(dp.date_of_payment, '%Y-%m-%d')
                UNION
                SELECT DATE_FORMAT(tp.date_paid, '%Y-%m-%d') AS date_sold FROM transfer_payments tp WHERE DATE_FORMAT(tp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(tp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') GROUP BY DATE_FORMAT(tp.date_paid, '%Y-%m-%d')
            ) AS dates ORDER BY dates.date_sold DESC) AS a
            
            
            LEFT JOIN
            (SELECT DATE_FORMAT(ss1.date_sold, '%Y-%m-%d') AS date_sold, SUM(ss1.unit_quantity * ss1.selling_price) AS gross_sales, SUM((ss1.selling_price-ss1.buying_price) * ss1.unit_quantity) AS gross_profit FROM sold_stock ss1
                WHERE DATE_FORMAT(ss1.date_sold, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ss1.date_sold, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(ss1.date_sold, '%Y-%m-%d')) AS cash_sales
            ON cash_sales.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') AS date_sold, SUM(ssr.quantity_returned*selling_price) AS sales_reversed, SUM((ssr.selling_price-buying_price)*ssr.quantity_returned) AS reversed_profit FROM sold_stock_reversed ssr
                WHERE DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND sale_type='cash'
                GROUP BY DATE_FORMAT(ssr.date_returned, '%Y-%m-%d')) AS reversed_cash
            ON reversed_cash.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') AS date_sold, SUM(ssd.unit_quantity * ssd.selling_price) AS gross_sales, SUM((ssd.selling_price-ssd.buying_price) * ssd.unit_quantity) AS gross_profit FROM sold_stock_debtors ssd
                WHERE DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(ssd.date_sold, '%Y-%m-%d')) AS debtor_sales
            ON debtor_sales.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') AS date_sold, SUM(ssr.quantity_returned*ssr.selling_price) AS sales_reversed, SUM((ssr.selling_price-ssr.buying_price)*ssr.quantity_returned) AS reversed_profit FROM sold_stock_reversed ssr
                WHERE DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND sale_type='debtor'
                GROUP BY DATE_FORMAT(ssr.date_returned, '%Y-%m-%d')) AS reversed_debtor
            ON reversed_debtor.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') AS date_sold, SUM(sst.unit_quantity * sst.buying_price) AS gross_sales FROM sold_stock_transfers sst
                WHERE DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(sst.date_transfered, '%Y-%m-%d')) AS transfer_sales
            ON transfer_sales.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') AS date_sold, SUM(ssr.quantity_returned*ssr.selling_price) AS sales_reversed FROM sold_stock_reversed ssr
                WHERE DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND sale_type='transfer'
                GROUP BY DATE_FORMAT(ssr.date_returned, '%Y-%m-%d')) AS reversed_transfer
            ON reversed_transfer.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') AS date_sold, SUM(ex.amount) AS expense FROM expenses ex
                WHERE DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') >=DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(ex.date_incurred, '%Y-%m-%d')) AS expenses
            ON expenses.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(of.date_created, '%Y-%m-%d') AS date_sold, SUM(of.float_amount) AS cash_float FROM opening_float of
                WHERE DATE_FORMAT(of.date_created, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(of.date_created, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(of.date_created, '%Y-%m-%d')) AS float_amount
            ON float_amount.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.cash) AS cash, SUM(sp.mpesa) AS mpesa, SUM(sp.bank) AS bank FROM sales_payments sp
                WHERE DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_amount
            ON banked_amount.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.amount_reversed) AS reversed_cash_amount FROM sales_payments sp
                WHERE sp.cash > 0 AND sp.mpesa < 1 AND sp.bank < 1 AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_reversed_cash_only
            ON banked_reversed_cash_only.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.amount_reversed) AS reversed_mpesa_amount FROM sales_payments sp
                WHERE sp.cash < 1 AND sp.mpesa > 0 AND sp.bank < 1 AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_reversed_mpesa_only
            ON banked_reversed_mpesa_only.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.amount_reversed) AS reversed_bank_amount FROM sales_payments sp
                WHERE sp.cash < 1 AND sp.mpesa < 1 AND sp.bank > 0 AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_reversed_bank_only
            ON banked_reversed_bank_only.date_sold=a.date_sold
            
            LEFT JOIN
            (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.amount_reversed) AS reversed_multiple FROM sales_payments sp
                WHERE DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND ((sp.cash > 0 AND sp.mpesa > 0) OR (sp.cash>0 AND sp.bank>0) OR (sp.mpesa>0 AND sp.bank > 0))
                GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_reversed_multiple
            ON banked_reversed_multiple.date_sold=a.date_sold

            LEFT JOIN
            (SELECT DATE_FORMAT(ss.date_sold, '%Y-%m-%d') AS date_sold, SUM((ss.recom_selling_price-selling_price) * ss.unit_quantity) AS discounts FROM sold_stock ss
                WHERE ss.selling_price<ss.recom_selling_price AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(ss.date_sold, '%Y-%m-%d')) AS cash_discounts
            ON cash_discounts.date_sold=a.date_sold

            LEFT JOIN
            (SELECT DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') AS date_sold, SUM((ssd.recom_selling_price-ssd.selling_price) * ssd.unit_quantity) AS discounts FROM sold_stock_debtors ssd
                WHERE ssd.selling_price<ssd.recom_selling_price AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(ssd.date_sold, '%Y-%m-%d')) AS debtor_discounts
            ON debtor_discounts.date_sold=a.date_sold

            LEFT JOIN
            (SELECT DATE_FORMAT(ss.date_sold, '%Y-%m-%d') AS date_sold, SUM((ss.selling_price-ss.recom_selling_price) * ss.unit_quantity) AS overcharges FROM sold_stock ss
                WHERE ss.selling_price>ss.recom_selling_price AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(ss.date_sold, '%Y-%m-%d')) AS cash_overcharges
            ON cash_overcharges.date_sold=a.date_sold

            LEFT JOIN
            (SELECT DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') AS date_sold, SUM((ssd.selling_price-ssd.recom_selling_price) * ssd.unit_quantity) AS overcharges FROM sold_stock_debtors ssd
                WHERE ssd.selling_price>ssd.recom_selling_price AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') >= DATE_FORMAT('2023-09-12', '%Y-%m-%d') AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') <= DATE_FORMAT('2023-09-12', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(ssd.date_sold, '%Y-%m-%d')) AS debtor_overcharges
            ON debtor_overcharges.date_sold=a.date_sold
            
            
            GROUP BY a.date_sold
            
            
            
            
            
            
            ) AS s GROUP BY date_sold