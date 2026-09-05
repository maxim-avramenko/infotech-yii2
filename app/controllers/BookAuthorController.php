<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\BookAuthor;
use app\models\BookAuthorForm;
use app\models\BookAuthorSubscriptionForm;
use app\models\User;
use app\services\BookAuthorService;
use app\services\BookAuthorSubscriptionService;
use InvalidArgumentException;
use Yii;
use yii\base\Module;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class BookAuthorController extends Controller
{
    public function __construct(
        string $id,
        Module $module,
        private readonly BookAuthorService $bookAuthorService,
        private readonly BookAuthorSubscriptionService $bookAuthorSubscriptionService,
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
                        'actions' => ['subscribe', 'view', 'top'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'search' => ['get'],
                    'subscribe' => ['post'],
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionSearch(string $q = ''): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return $this->bookAuthorService->search($q);
    }

    public function actionSubscribe(): Response
    {
        $form = new BookAuthorSubscriptionForm();
        $form->load(Yii::$app->request->post());
        $bookId = (int) Yii::$app->request->post('bookId');
        $redirect = $bookId > 0 ? ['/book/view', 'id' => $bookId] : ['/site/index'];

        if (!$form->validate()) {
            Yii::$app->session->setFlash('error', $this->firstFormError($form));

            return $this->redirect($redirect);
        }

        try {
            $this->bookAuthorSubscriptionService->subscribe((int) $form->bookAuthorId, $form->phone);
        } catch (InvalidArgumentException $exception) {
            Yii::$app->session->setFlash('error', $exception->getMessage());

            return $this->redirect($redirect);
        }

        Yii::$app->session->setFlash('success', 'You are subscribed to new books by this author.');

        return $this->redirect($redirect);
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'dataProvider' => $this->bookAuthorService->list(),
        ]);
    }

    public function actionTop(?int $year = null): string
    {
        $years = $this->bookAuthorService->yearsWithBooks();
        $selectedYear = $year ?? ($years[0] ?? (int) date('Y'));
        if ($selectedYear < 1 || $selectedYear > 9999) {
            $selectedYear = $years[0] ?? (int) date('Y');
        }
        if ($years !== [] && !in_array($selectedYear, $years, true)) {
            $years[] = $selectedYear;
            rsort($years);
        }

        return $this->render('top', [
            'year' => $selectedYear,
            'years' => $years === [] ? [$selectedYear] : $years,
            'authors' => $this->bookAuthorService->topByYear($selectedYear),
        ]);
    }

    public function actionView(int $id): string
    {
        $author = $this->requireAuthor($id);

        return $this->render('view', [
            'model' => $author,
            'books' => $this->bookAuthorService->books($author),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new BookAuthorForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $author = $this->bookAuthorService->create(
                    $form->lastname,
                    $form->firstname,
                    $form->secondname,
                    $this->currentUser(),
                );
            } catch (InvalidArgumentException $exception) {
                $form->addError('lastname', $exception->getMessage());

                return $this->render('create', ['model' => $form]);
            }

            Yii::$app->session->setFlash('success', 'Author created.');

            return $this->redirect(['view', 'id' => $author->id]);
        }

        return $this->render('create', [
            'model' => $form,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $author = $this->requireAuthor($id);
        $form = BookAuthorForm::fromAuthor($author);
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->bookAuthorService->update(
                    $author,
                    $form->lastname,
                    $form->firstname,
                    $form->secondname,
                );
            } catch (InvalidArgumentException $exception) {
                $form->addError('lastname', $exception->getMessage());

                return $this->render('update', [
                    'model' => $form,
                    'author' => $author,
                ]);
            }

            Yii::$app->session->setFlash('success', 'Author updated.');

            return $this->redirect(['view', 'id' => $author->id]);
        }

        return $this->render('update', [
            'model' => $form,
            'author' => $author,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->bookAuthorService->delete($this->requireAuthor($id));
        Yii::$app->session->setFlash('success', 'Author deleted.');

        return $this->redirect(['index']);
    }

    private function firstFormError(BookAuthorSubscriptionForm $form): string
    {
        $errors = $form->getFirstErrors();
        $message = $errors === [] ? '' : (string) reset($errors);

        return $message !== '' ? $message : 'Unable to subscribe.';
    }

    private function requireAuthor(int $id): BookAuthor
    {
        $author = $this->bookAuthorService->findById($id);
        if ($author === null) {
            throw new NotFoundHttpException('The requested author does not exist.');
        }

        return $author;
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
