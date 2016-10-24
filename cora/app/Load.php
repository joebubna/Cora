<?PHP
namespace Cora\App;

class Load extends \Cora\Load
{   
	public function display($bool, $displayIfTrue = '', $displayIfFalse = 'hide')
	{
		if(!$bool) {
			echo $displayIfFalse;
		}
		else
			echo $displayIfTrue;
	}
    
    public function active($bool)
	{
		if(!$bool) {
			echo ('disabled');
		}
		else
			echo('');
	} 
}