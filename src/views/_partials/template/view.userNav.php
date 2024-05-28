<!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="ifuel_menu_nav">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="/"><i class="glyphicon glyphicon-home icon-white"></i>Home</a>
                    </li>
                    <li>
                        <a href="/users/profile/<?= $cuser->id; ?>"><i class="glyphicon glyphicon-user icon-white"></i>My Profile</a>
                    </li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="/users/logout/">Logout</a>
                    </li>
                </ul>
            </div>