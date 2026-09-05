<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Book;
use app\models\BookForm;
use app\models\User;
use app\services\BookService;
use InvalidArgumentException;
use Yii;
use yii\base\Module;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class BookController extends Controller
{
    public function __construct(
        string $id,
        Module $module,
        private readonly BookService $bookService,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): Response
    {
        return $this->redirect(['/site/index']);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model' => $this->requireBook($id),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new BookForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $book = $this->bookService->create(
                    $form->title,
                    (int) $form->year,
                    $form->description,
                    $form->isbn,
                    $form->normalizedAuthorIds(),
                    $this->currentUser(),
                    $form->imageFile,
                );
            } catch (InvalidArgumentException $exception) {
                $form->addError($this->errorAttribute($exception), $exception->getMessage());

                return $this->render('create', $this->formParams($form));
            }

            Yii::$app->session->setFlash('success', 'Book created.');

            return $this->redirect(['view', 'id' => $book->id]);
        }

        return $this->render('create', $this->formParams($form));
    }

    public function actionUpdate(int $id): Response|string
    {
        $book = $this->requireBook($id);
        $form = BookForm::fromBook($book);
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->bookService->update(
                    $book,
                    $form->title,
                    (int) $form->year,
                    $form->description,
                    $form->isbn,
                    $form->normalizedAuthorIds(),
                    $form->imageFile,
                );
            } catch (InvalidArgumentException $exception) {
                $form->addError($this->errorAttribute($exception), $exception->getMessage());

                return $this->render('update', $this->formParams($form, $book));
            }

            Yii::$app->session->setFlash('success', 'Book updated.');

            return $this->redirect(['view', 'id' => $book->id]);
        }

        return $this->render('update', $this->formParams($form, $book));
    }

    public function actionDelete(int $id): Response
    {
        $this->bookService->delete($this->requireBook($id));
        Yii::$app->session->setFlash('success', 'Book deleted.');

        return $this->redirect(['/site/index']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formParams(BookForm $form, ?Book $book = null): array
    {
        $params = [
            'model' => $form,
            'authors' => $this->bookService->authorOptions($form->normalizedAuthorIds()),
        ];
        if ($book !== null) {
            $params['book'] = $book;
        }

        return $params;
    }

    private function errorAttribute(InvalidArgumentException $exception): string
    {
        $message = $exception->getMessage();
        if (str_contains($message, 'Author')) {
            return 'authorIds';
        }
        if (str_contains($message, 'ISBN')) {
            return 'isbn';
        }
        if (str_contains($message, 'Image') || str_contains($message, 'JPEG')) {
            return 'imageFile';
        }

        return 'title';
    }

    private function requireBook(int $id): Book
    {
        $book = $this->bookService->findById($id);
        if ($book === null) {
            throw new NotFoundHttpException('The requested book does not exist.');
        }

        return $book;
    }

    private function currentUser(): User
    {
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User) {
            throw new ForbiddenHttpException('User is not authenticated.');
        }

        return $identity;
    }
}
