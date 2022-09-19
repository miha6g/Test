create table "orders" (
    firstname varchar(255) not null default '',
    lastname varchar(255) not null default '',
    phone varchar(10) not null default '',
    person_type varchar(255) not null default '',
    contract_number varchar(255) not null default '',
    created_at timestamp with time zone not null default 'now'
);