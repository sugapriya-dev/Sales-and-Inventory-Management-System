--create databse

create database IF NOT Exists supermarket;
use supermarket;

--create table users
create table IF not Exists users (id int primary key AUTO_INCREMENT,username varchar(50) not null,email varchar (100) not null,password varchar(50) not null,role ENUM('admin','staff') default 'staff',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,lastlogin TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

--create table customers
create table if NOT Exists customers (id int primary key AUTO_INCREMENT,customername varchar(50) not null,phoneno int not null,city varchar(50)not null,created_at TIMESTAMP default CURRENT_TIMESTAMP);

ALTER TABLE customers 
MODIFY phoneno BIGINT NOT NULL;

alter table customers add opening_amt int not null;

alter table customers add aadhaarno varchar(20)not null;

---create table suppliers

create table if not Exists suppliers(id int primary key AUTO_INCREMENT,suppliers_name varchar(50) not null,phoneno int not null,email varchar(200) not null,sup_address varchar(200)not null,gst_no varchar(50) not null,created_at TIMESTAMP default CURRENT_TIMESTAMP,updated_at TIMESTAMP default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP );

ALTER TABLE suppliers 
MODIFY phoneno VARCHAR(15);

ALTER TABLE suppliers ADD UNIQUE (phoneno);

ALTER TABLE suppliers
MODIFY phoneno VARCHAR(10) NOT NULL;

alter table suppliers add city varchar(50) not null;
alter table suppliers add opening_amt int not null;
alter table suppliers add sup_state varchar(50) not null;

ALTER TABLE suppliers 
ADD account_id INT NOT NULL;

alter table suppliers drop COLUMN account_id;


---create products
CREATE TABLE products (pid INT PRIMARY KEY AUTO_INCREMENT,pro_name VARCHAR(100) NOT NULL,category VARCHAR(50) NOT NULL,unittype VARCHAR(20) NOT NULL,unitprice DECIMAL(10,2) NOT NULL,purchaseprice DECIMAL(10,2) NOT NULL,regularprice DECIMAL(10,2) NOT NULL,taxtype VARCHAR(10) NOT NULL,taxrate DECIMAL(5,2) NOT NULL,hsncode VARCHAR(20) NOT NULL,initialqty INT NOT NULL,expirydate DATE DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

ALTER TABLE products 
MODIFY expirydate DATE DEFAULT NULL;

ALTER TABLE products DROP COLUMN taxrate;


---create company table
CREATE TABLE if not Exists company (id INT AUTO_INCREMENT PRIMARY KEY,comp_name VARCHAR(255) NOT NULL,comp_address TEXT default null,phone VARCHAR(20) not null,email VARCHAR(100) not null,gstno VARCHAR(15) not null,comp_state varchar(50)not null,website VARCHAR(255) not null,opening_amt int not null);


INSERT INTO company (comp_name, comp_address, phone, email, gstno, comp_state, website, opening_amt) VALUES('NISQUARE TECH', 'Virudhunagar', '9876543210', 'nisquaretech@gmail.com', '29ABCDE1234F1Z5', 'Tamilnadu','https://www.nisquaretech.in/', ₹20,00,000.00);

alter table company add logo varchar(20) not null;

UPDATE company SET logo = 'weblogo.png' WHERE id = 1;

ALTER TABLE company CHANGE opening_amt opening_cash INT;

---create table bank

CREATE TABLE IF NOT EXISTS bank (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(50) NOT NULL,
    accname VARCHAR(50) NOT NULL,
    accno VARCHAR(20) NOT NULL,
    branch_name VARCHAR(50) NOT NULL,
    opening_cash INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE bank ADD account_id INT NOT NULL;

alter table bank drop COLUMN account_id;

---create table gst slab

create table if not Exists gstslab(id int not null AUTO_INCREMENT PRIMARY KEY,name varchar(50)not null,gst_percent decimal(5,2) not null,pricetype varchar(20)not null,created_at TIMESTAMP default CURRENT_TIMESTAMP);

---create table purchase

CREATE TABLE IF NOT EXISTS purchase (id INT AUTO_INCREMENT PRIMARY KEY,
supplier_id INT NOT NULL,date DATE NOT NULL,invoiceno VARCHAR(50),mode ENUM('cash','credit','bank') NOT NULL,bankname VARCHAR(50) NULL,totalamt DECIMAL(10,2) DEFAULT 0.00,paidamt DECIMAL(10,2) DEFAULT 0.00,balanceamt DECIMAL(10,2) DEFAULT 0.00,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (supplier_id) REFERENCES suppliers(id)ON DELETE CASCADE ON UPDATE CASCADE
);


ALTER TABLE purchase ADD bank_id INT NULL;

alter table purchase add discount_total DECIMAL(10,2)DEFAULT 0.00;
alter table purchase add gst_total DECIMAL(10,2) DEFAULT 0.00;
   alter table purchase add packing_charge DECIMAL(10,2) DEFAULT 0.00;
   alter table purchase add round_off DECIMAL(10,2) DEFAULT 0.00;
   alter table purchase add grand_total DECIMAL(10,2) DEFAULT 0.00;


---create table billing transaction

create table if not Exists billtransaction(id int AUTO_INCREMENT primary key,date_from date not null,date_to date not null,transaction_type ENUM('cash','upi') NOT NULL);

--billing podala 

---create table purchase items

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    gst_percent DECIMAL(5,2) DEFAULT 0,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchase(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(pid) ON DELETE CASCADE
);




-- CREATE TABLE IF NOT EXISTS purchase_payments (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     supplier_id INT NOT NULL,
--     payment_date DATE NOT NULL,
--     amount DECIMAL(10,2) NOT NULL,
--     payment_mode ENUM('cash', 'bank', 'cheque') NOT NULL,
--     bank_name VARCHAR(50),
--     reference_no VARCHAR(50),
--     notes TEXT,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
-- );


--create table sales

-- CREATE TABLE sales (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     customer_id INT,
--     invoice_no VARCHAR(50),
--     sale_date DATE,
--     payment_mode ENUM('cash','bank','credit'),
--     total_amount DECIMAL(10,2),
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );


-- --create table sale_items

-- CREATE TABLE sale_items (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     sale_id INT,
--     product_name VARCHAR(100),
--     qty INT,
--     price DECIMAL(10,2),
--     total DECIMAL(10,2)
-- );


-- CREATE TABLE journal_entries (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     entry_date DATE NOT NULL,
--     narration VARCHAR(255),
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );


-- CREATE TABLE journal_items (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     journal_id INT,
--     account_id INT,
--     debit DECIMAL(10,2) DEFAULT 0,
--     credit DECIMAL(10,2) DEFAULT 0,
--     FOREIGN KEY (journal_id) REFERENCES journal_entries(id),
--     FOREIGN KEY (account_id) REFERENCES accounts(id)
-- );


-- CREATE TABLE accounts (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     account_name VARCHAR(100) NOT NULL,
--     account_type ENUM('Asset','Liability','Income','Expense') NOT NULL
-- );


CREATE TABLE IF NOT EXISTS cash_in_hand (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('purchase', 'sale', 'expense', 'receipt', 'payment') NOT NULL,
    reference_id INT,
    reference_no VARCHAR(100),
    particulars VARCHAR(255),
    debit DECIMAL(10,2) DEFAULT 0, -- Cash IN (receipts, sales)
    credit DECIMAL(10,2) DEFAULT 0, -- Cash OUT (purchases, expenses)
    balance DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);





-- Supplier Payments Table
CREATE TABLE IF NOT EXISTS supplier_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_mode ENUM('cash', 'bank', 'cheque') NOT NULL,
    bank_name VARCHAR(50),
    reference_no VARCHAR(100),
    cheque_no VARCHAR(50),
    cheque_date DATE,
    particulars TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
);

ALTER TABLE supplier_payments MODIFY payment_mode ENUM('cash', 'bank') NOT NULL;

ALTER TABLE supplier_payments DROP COLUMN cheque_date,DROP COLUMN cheque_no;


-- Supplier Ledger Table
CREATE TABLE IF NOT EXISTS supplier_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('purchase', 'payment', 'return', 'opening') NOT NULL,
    reference_id INT,
    reference_no VARCHAR(100),
    particulars VARCHAR(255),
    debit DECIMAL(10,2) DEFAULT 0,
    credit DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0,
    payment_mode VARCHAR(20),
    bank_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
);

-- Purchase Returns Table
CREATE TABLE IF NOT EXISTS purchase_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    purchase_id INT,
    return_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    reference_no VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_id) REFERENCES purchase(id) ON DELETE SET NULL
);

-- Bank Ledger Table
CREATE TABLE IF NOT EXISTS bank_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_id INT,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('payment', 'receipt', 'transfer') NOT NULL,
    reference_id INT,
    reference_no VARCHAR(100),
    particulars VARCHAR(255),
    withdrawal DECIMAL(10,2) DEFAULT 0,
    deposit DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);




CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_id INT NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Cash','Bank') NOT NULL,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (bank_id)
    REFERENCES bank(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);


INSERT INTO accounts (bank_id, account_name, account_type, balance)
VALUES (1, 'SBI Ledger', 'Bank', 300000);

INSERT INTO accounts (bank_id, account_name, account_type, balance)
VALUES (3, 'TMB Ledger', 'Bank', 500000);


alter table accounts add transaction_date DATE NULL;
    alter table accounts add transaction_type ENUM('payment', 'receipt', 'transfer') NOT NULL;

   alter table accounts add  reference_id INT NULL;
    alter table accounts add reference_no VARCHAR(100);
   alter table accounts add  particulars VARCHAR(255);

     alter table accounts add debit DECIMAL(12,2) DEFAULT 0;
   alter table accounts add  credit DECIMAL(12,2) DEFAULT 0;
  

-- sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    date DATE NOT NULL,
    invoiceno VARCHAR(50),
    mode ENUM('cash', 'credit', 'bank') NOT NULL,
    bankname VARCHAR(50) NULL,
    bank_id INT NULL,
    totalamt DECIMAL(10,2) DEFAULT 0.00,
    paidamt DECIMAL(10,2) DEFAULT 0.00,
    balanceamt DECIMAL(10,2) DEFAULT 0.00,
    discount_total DECIMAL(10,2) DEFAULT 0.00,
    gst_total DECIMAL(10,2) DEFAULT 0.00,
    packing_charge DECIMAL(10,2) DEFAULT 0.00,
    round_off DECIMAL(10,2) DEFAULT 0.00,
    grand_total DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Simple sales items table (like your purchase_items)
CREATE TABLE IF NOT EXISTS sales_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sales_id INT NOT NULL,
    product_id INT NOT NULL,
    gst_percent DECIMAL(5,2) DEFAULT 0,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sales_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(pid) ON DELETE CASCADE
);

-- You can drop the bills table if using this structure
-- DROP TABLE IF EXISTS bills;


--create table bill

create table bills( id int AUTO_INCREMENT PRIMARY key,billno varchar(50)not null,totamt decimal(10,2)not null,discount_price decimal(10,2)not null,gst_amt decimal(10,2),packing_amt DECIMAL(10,2),final_amt decimal(10,2)not null,payment_mode varchar(20) DEFAULT 'Cash',
 sales_id int not null,
  bank_account_id int DEFAULT NULL,
  customer_name varchar(255) DEFAULT NULL,
  customer_city varchar(255) DEFAULT NULL,
  customer_state varchar(100) DEFAULT NULL,
  customer_phone varchar(20) DEFAULT NULL,
  customer_aadhar varchar(20) DEFAULT NULL,
  customer_id int(11) DEFAULT NULL,
  discount_slab_id int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  FOREIGN key (sales_id) REFERENCES customers(id) on DELETE CASCADE,
  FOREIGN KEY(discount_slab_id) REFERENCES gstslab(id)on DELETE CASCADE
);


--table category

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_code VARCHAR(50),
    description TEXT,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

alter table categories drop COLUMN description;



-- Sales Payments Table
CREATE TABLE IF NOT EXISTS sales_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_mode ENUM('cash', 'bank') NOT NULL,
    bank_name VARCHAR(50),
    reference_no VARCHAR(100),
    cheque_no VARCHAR(50),
    cheque_date DATE,
    particulars TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);