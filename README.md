# CYBERMART SQL Injection Lab

## Goal

Two PHP/MySQL sites share the same UI and fictitious data:

- **Vulnerable** concatenates supplied input into SQL.
- **Protected** uses native PDO prepared statements with bound values.

Run the lab only on an isolated local, host-only, or internal network. It is deliberately not a production authentication system: the passwords are plain-text demo data.

## Windows WAMP quick start

1. Start Apache and MySQL or MariaDB in WAMP, and ensure the `pdo_mysql` PHP extension is enabled.
2. In phpMyAdmin, sign in as an administrative database user, select **Import**, and run [sql/init.sql](sql/init.sql). Re-running it resets the fictitious tables and restores the lab account to `SELECT`-only access.
3. Open the local lab pages:
   - `http://localhost/sqli_dual_lab/public/vulnerable/`
   - `http://localhost/sqli_dual_lab/public/secure/`

## Ubuntu Server setup

Install the required services:

```bash
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql unzip -y
sudo systemctl enable --now apache2 mysql
```

Initialize the fictitious database from the project root:

```bash
sudo mysql < sql/init.sql
```

For an isolated Ubuntu server, publish the project beneath Apache's document root:

```bash
sudo cp -r /opt/sqli_dual_lab /var/www/html/sqli-lab
sudo chown -R root:www-data /var/www/html/sqli-lab
sudo find /var/www/html/sqli-lab -type d -exec chmod 750 {} \;
sudo find /var/www/html/sqli-lab -type f -exec chmod 640 {} \;
sudo systemctl restart apache2
```

The two pages are then available at:

- `http://UBUNTU_SERVER_IP/sqli-lab/public/vulnerable/`
- `http://UBUNTU_SERVER_IP/sqli-lab/public/secure/`

Keep this intentionally vulnerable lab off public networks. In a real application, keep configuration and database credentials outside the webroot.

## Five attacks on each main page

After updating the source, import `sql/init.sql` again. Re-importing resets all fictitious lab data.

Both main pages now contain the same five local-only comparisons directly; there is no separate attack-exercises page:

- `http://localhost/sqli_dual_lab/public/vulnerable/`
- `http://localhost/sqli_dual_lab/public/secure/`

Each attack has a copyable test payload, an input form, visible returned data or response, the SQL explanation, and the executed query (or its bound value):

1. Authentication-bypass query and the account it returns.
2. UNION-based query and its returned rows.
3. Error-based exposure through a deliberately malformed catalog query.
4. Boolean-based blind responses with only true/false output.
5. Time-based blind responses with measured query time.
The time-based card requests a two-second per-query cap where the installed MySQL-family server supports one. Do not use long-running inputs.

## Lab demonstration

Use the Copy button on an attack card, then run the same locally controlled input on both pages to compare their behavior. For the authentication-bypass card, set the username to:

```text
' OR 1=1 -- 
```

Use any password. The space after `--` is required by MySQL comment syntax. The vulnerable page returns an account; the protected page denies it and displays the parameter-bound query.

Every card places the query trace directly under its result. The vulnerable build displays the raw SQL string; the protected build displays the SQL template and the values bound separately from it.

## Lab boundaries and mitigation

The database account used by the website has `SELECT` access only, and its PDO connection refuses multiple statements. This keeps the exercise focused on observing a single-query authentication or search bypass.

In production, use parameterized queries everywhere, password hashes with `password_hash` and `password_verify`, rate limiting, secure session handling, error logging, and an account with only the privileges the application requires.

For network exercises, use host-only or internal virtual networking for Ubuntu and Kali. Do not use bridged networking, internet port forwarding, traffic flooding, or probes against systems you do not own.
