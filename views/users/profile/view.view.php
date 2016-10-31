<h3><?= $user->firstName . ' ' . $user->lastName;  ?></h3>
<h4><?= $user->email; ?></h4>
<h4><?= $user->primaryRole->name; ?></h4>
		
<a href="/users/profile/edit/<?= $user->id; ?>/">Edit</a>
