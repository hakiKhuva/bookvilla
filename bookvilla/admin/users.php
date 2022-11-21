<?php
session_start();

require_once("includes/helpers.php");

AdminRequired();

require_once("includes/connection.php");

$Page = 1;
$UsersPerPage = 10;

$db_keys = array(
    "id" => "users.id",
    "name" => "users.u_name",
    "uuid" => "users.uuid",
    "email" => "users.u_email"
);

$orders = array(
    "asc" => "ASC",
    "desc" => "DESC"
);

if(isset($_GET["using"]) && isset($_GET["pattern"]) && isset($_GET["order"])){
    $using = $db_keys[$_GET["using"]] or "users.id";
    $pattern = "\"".$_GET["pattern"]."\"";
    $order = $orders[$_GET["order"]] or "ASC";

    $sql1 = "SELECT COUNT(*) FROM users WHERE $using LIKE $pattern ORDER BY $using $order";
    $sql2 = "SELECT * FROM users WHERE $using LIKE $pattern ORDER BY $using $order LIMIT ?,?";
}

if(isset($_GET["page"])){
    $Page = $_GET["page"];
}

$LimitFrom = ($Page * $UsersPerPage) - $UsersPerPage;

$connection = getConnection();

if(isset($sql1)){
    $sql = $connection->prepare($sql1);
} else {
    $sql = $connection->prepare("SELECT COUNT(*) FROM users");
}
$sql->execute();

$users = $sql->get_result();
$TotalUsers = $users -> fetch_row()[0];

if(isset($sql2)){
    $sql = $connection->prepare($sql2);
} else {
    $sql = $connection->prepare("SELECT id, u_name, u_email, u_role_type, uuid, created_at FROM users LIMIT ?,?");
}
$sql->bind_param("ii", $LimitFrom, $UsersPerPage);

$sql->execute();

$users = $sql->get_result();

$usersPage = ceil($TotalUsers / $UsersPerPage);

$PageTitle = "Users";
$stylesheets = array("../static/css/search.css", "static/css/users.css");
require_once("includes/header.php");

?>

<div>
    <div class="options">
    <h1>Users</h1>
    <div class="options">
        <div>
            <strong>Total records : <?= $TotalUsers ?></strong>
        </div>

        <details>
            <summary>Search users</summary>
            <form action="users.php" method="GET" class="form-w" style="max-width: 350px; position: absolute; border: 1px solid var(--fg);">
                <div class="label">
                    <label for="using">Search using</label>
                    <select name="using" id="using" class="input">
                        <option value="id">ID</option>
                        <option value="name">Name</option>
                        <option value="email">Email</option>
                        <option value="uuid">UUID</option>
                    </select>
                </div>
                <div class="label">
                    <label for="pattern">Search Pattern</label>
                    <input type="text" name="pattern" id="pattern" required />
                </div>
                <div class="label">
                    <label for="order">ORDER by</label>
                    <select name="order" id="order" class="input">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
                <button type="submit">Search</button>
            </form>
        </details>

        <form action="get" class="select-form" style="margin: 0px;padding: 0px;">
            <select name="page" id="page" class="round" onchange="page_change_jump(this)">
                <option hidden>Jump To Page</option>
                <?php

                $p_c = 1;

                while ($p_c <= $usersPage) {
                ?>
                    <option value="<?= $p_c ?>"><?= $p_c ?></option>
                <?php
                    $p_c++;
                }

                ?>
            </select>
        </form>

    </div>
    </div>
    <?php
    if ($users && $TotalUsers > 0) { ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>UUID</th>
                <th>Actions</th>
            </tr>
            <?php
            while ($data = $users->fetch_assoc()) {
            ?>
                <tr>
                    <td><?= $data["id"] ?></td>
                    <td><?= $data["u_name"] ?></td>
                    <td><?= $data["u_email"] ?></td>
                    <td><?= $data["u_role_type"] === "admin_user" ? "Admin" : "Normal" ?></td>
                    <td><?= $data["uuid"] ?></td>
                    <td>
                        <a href="user.php?uuid=<?= $data["uuid"] ?>">View record</a>
                    </td>
                </tr>
            <?php
            }
            ?>
        </table>
    <?php

    } else {
    ?>
        <div style="text-align: center;">
            <div class="header">No users found!</div>
        </div>
    <?php
    }
    ?>
</div>

<?php
closeConnection($connection);

require_once("includes/footer.php");
?>