import pymysql

# Patch MySQLdb with PyMySQL for pure-python driver compatibility
pymysql.install_as_MySQLdb()

