
require_once __DIR__.'/../../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;
use App\Entity\Transaction;
use App\Service\TransactionService;

class TransactionResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testPostTransaction(): void
    {
        $this->writeTestDescription("TRANSACTIONS");
        $client = self::createClient();
        $entity_manager = self::$container->get(EntityManagerInterface::class);
        $service = new TransactionService($entity_manager);

        // Create users.
        $uid = '111';
        $user = $this->createUser($uid, 'foo', 'en');
        $user = $this->createUser('1486', 'foo', 'en');

        $this->writeTestDescription("Post Transaction for $uid");
        for($i = 0; $i < 50; $i++) {
            $r = rand(0,50);
            $amount = 100 * intval($uid) + $i * 10 + $r + $i/100;
            $status_code = $service->postItem('USD', $amount, $uid, '1486', 'SBB Income');
        }
        // Use Transaction service to post Transaction.
        $this->assertEquals(201, $status_code, "201 Response expected, but $status_code given.");

    }

}
