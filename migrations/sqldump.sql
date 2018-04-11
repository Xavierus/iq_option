DROP TABLE IF EXISTS user;
CREATE TABLE user (
  user_id int(11) NOT NULL AUTO_INCREMENT,
  balance DECIMAL(13, 2) DEFAULT 0,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB;

INSERT INTO user VALUES (1, 0);
INSERT INTO user VALUES (2, 0);
INSERT INTO user VALUES (3, 0);
INSERT INTO user VALUES (4, 0);


DROP TABLE IF EXISTS user_balance_transactions;
CREATE TABLE user_balance_transaction (
  user_balance_transaction_id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  sum DECIMAL(13, 2) NOT NULL,
  PRIMARY KEY (user_balance_transaction_id),
  FOREIGN KEY (user_id) REFERENCES user(user_id)
) ENGINE=InnoDB;