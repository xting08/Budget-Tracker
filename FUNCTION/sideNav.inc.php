<header>
    <section id="side-nav">
        <button id="small-profile" onclick="window.location.href='profile.php'">
            <img src="../FUNCTION/getProfilePic.php" alt="Profile Picture" id="profile-pic">
            <span id="username"><?php echo $username; ?> &nbsp;</span>
        </button>
        <div id="side-nav-options">
            <button onclick="window.location.href='main.php'">DASHBOARD</button>
            <button onclick="window.location.href='transaction.php'">TRANSACTION</button>
            <button onclick="window.location.href='budget.php'">BUDGET</button>
            <button onclick="window.location.href='savingGoals.php'">SAVING GOAL</button>
            <button onclick="window.location.href='shareExpense.php'">SHARE EXPENSE</button>
            <button onclick="window.location.href='categories.php'">CATEGORIES</button>
        </div>
        <div id="side-nav-options-bottom">
            <button onclick="window.open('https://forms.gle/wMaahXK2hi4NFExt5', '_blank')">REPORT</button>
        </div>
    </section>
</header>