BEGIN;

  DROP TABLE IF EXISTS user_balance_transaction;
  DROP TABLE IF EXISTS user_balance_transaction_state;
  DROP TABLE IF EXISTS user;

  CREATE TABLE user (
    user_id int(11) NOT NULL AUTO_INCREMENT,
    balance DECIMAL(13, 2) DEFAULT 0,
    PRIMARY KEY (user_id)
  ) ENGINE=InnoDB;

  INSERT INTO user VALUES (1, 101);
  INSERT INTO user VALUES (2, 0);
  INSERT INTO user VALUES (3, 0);
  INSERT INTO user VALUES (4, 0);


  CREATE TABLE user_balance_transaction_state (
    user_balance_transaction_state_id int(11) NOT NULL AUTO_INCREMENT,
    state VARCHAR(10) NOT NULL UNIQUE,
    PRIMARY KEY (user_balance_transaction_state_id)
  ) ENGINE=InnoDB;

  INSERT INTO user_balance_transaction_state
  VALUES
    (1, 'dirty'),
    (2, 'pending'),
    (3, 'rolledback'),
    (4, 'commited'),
    (5, 'failed');


  CREATE TABLE user_balance_transaction_type (
    user_balance_transaction_type_id int(11) NOT NULL AUTO_INCREMENT,
    type VARCHAR(15) NOT NULL UNIQUE,
    PRIMARY KEY (user_balance_transaction_type_id)
  ) ENGINE=InnoDB;

  INSERT INTO user_balance_transaction_type
  VALUES
    (1, 'debit'),
    (2, 'credit'),
    (3, 'transfer'),
    (4, 'commit'),
    (5, 'rollback');


  CREATE TABLE user_balance_transaction (
    user_balance_transaction_id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    sum DECIMAL(13, 2) NOT NULL,
    user_balance_transaction_state_id int(11) NOT NULL,
    user_balance_transaction_type_id int(11) NOT NULL,
    PRIMARY KEY (user_balance_transaction_id),
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (user_balance_transaction_state_id) REFERENCES user_balance_transaction_state(user_balance_transaction_state_id),
    FOREIGN KEY (user_balance_transaction_type_id) REFERENCES user_balance_transaction_type(user_balance_transaction_type_id)
  ) ENGINE=InnoDB;

COMMIT;