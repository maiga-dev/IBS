<?php

declare(strict_types=1);

/**
 * @var Laminas\View\Renderer\PhpRenderer $this
 * @var array $equipement
 * @var array $types
 * @var array $sites
 * @var array $centrales
 * @var string|null $error
 */

$equipement = $equipement ?? [];
$types = $types ?? [];
$sites = $sites ?? [];
$centrales = $centrales ?? [];
$error = $error ?? null;

?>

<div class="dashboard">

    <div class="dashboard-header">

        <div>
            <h1>Modifier l'équipement</h1>

            <p>
                Modifier les informations de l'équipement.
            </p>
        </div>

    </div>


    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= $this->escapeHtml($error) ?>
        </div>

    <?php endif; ?>


    <div class="dashboard-panel">

        <form
            method="post"
            action="<?= $this->url(
                'equipement/edit',
                [
                    'id' =>
                        (int) $equipement['id_equipement'],
                ]
            ) ?>"
        >

            <div class="form-grid">


                <!-- TYPE -->

                <div class="form-group">

                    <label for="id_type_equipement">
                        Type d'équipement
                    </label>

                    <select
                        name="id_type_equipement"
                        id="id_type_equipement"
                        class="ibs-input"
                        required
                    >

                        <option value="">
                            Sélectionner un type
                        </option>

                        <?php foreach ($types as $type): ?>

                            <option
                                value="<?= (int) $type[
                                    'id_type_equipement'
                                ] ?>"
                                <?= (
                                    (int) (
                                        $equipement[
                                            'id_type_equipement'
                                        ] ?? 0
                                    )
                                    ===
                                    (int) $type[
                                        'id_type_equipement'
                                    ]
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= $this->escapeHtml(
                                    $type['nom_type'] ?? '-'
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- NUMERO SERIE -->

                <div class="form-group">

                    <label for="numero_serie">
                        Numéro de série
                    </label>

                    <input
                        type="text"
                        name="numero_serie"
                        id="numero_serie"
                        class="ibs-input"
                        value="<?= $this->escapeHtmlAttr(
                            $equipement['numero_serie'] ?? ''
                        ) ?>"
                    >

                </div>


                <!-- MARQUE -->

                <div class="form-group">

                    <label for="marque">
                        Marque
                    </label>

                    <input
                        type="text"
                        name="marque"
                        id="marque"
                        class="ibs-input"
                        value="<?= $this->escapeHtmlAttr(
                            $equipement['marque'] ?? ''
                        ) ?>"
                    >

                </div>


                <!-- MODELE -->

                <div class="form-group">

                    <label for="modele">
                        Modèle
                    </label>

                    <input
                        type="text"
                        name="modele"
                        id="modele"
                        class="ibs-input"
                        value="<?= $this->escapeHtmlAttr(
                            $equipement['modele'] ?? ''
                        ) ?>"
                    >

                </div>


                <!-- SITE -->

                <div class="form-group">

                    <label for="id_site">
                        Site
                    </label>

                    <select
                        name="id_site"
                        id="id_site"
                        class="ibs-input"
                    >

                        <option value="">
                            Sélectionner un site
                        </option>

                        <?php foreach ($sites as $site): ?>

                            <option
                                value="<?= (int) $site['id_site'] ?>"
                                <?= (
                                    (int) (
                                        $equipement['id_site'] ?? 0
                                    )
                                    ===
                                    (int) $site['id_site']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= $this->escapeHtml(
                                    $site['nom_site'] ?? '-'
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- CENTRALE -->

                <div class="form-group">

                    <label for="id_centrale">
                        Centrale
                    </label>

                    <select
                        name="id_centrale"
                        id="id_centrale"
                        class="ibs-input"
                    >

                        <option value="">
                            Sélectionner une centrale
                        </option>

                        <?php foreach ($centrales as $centrale): ?>

                            <option
                                value="<?= (int) $centrale[
                                    'id_centrale'
                                ] ?>"
                                <?= (
                                    (int) (
                                        $equipement[
                                            'id_centrale'
                                        ] ?? 0
                                    )
                                    ===
                                    (int) $centrale[
                                        'id_centrale'
                                    ]
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= $this->escapeHtml(
                                    $centrale[
                                        'type_centrale'
                                    ] ?? '-'
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- DATE -->

                <div class="form-group">

                    <label for="date_mise_en_service">
                        Date de mise en service
                    </label>

                    <input
                        type="date"
                        name="date_mise_en_service"
                        id="date_mise_en_service"
                        class="ibs-input"
                        value="<?= $this->escapeHtmlAttr(
                            $equipement[
                                'date_mise_en_service'
                            ] ?? ''
                        ) ?>"
                    >

                </div>


                <!-- PERIODICITE -->

                <div class="form-group">

                    <label for="periodicite">
                        Périodicité maintenance
                    </label>

                    <input
                        type="text"
                        name="periodicite"
                        id="periodicite"
                        class="ibs-input"
                        value="<?= $this->escapeHtmlAttr(
                            $equipement['periodicite'] ?? ''
                        ) ?>"
                    >

                </div>


                <!-- PROCHAINE MAINTENANCE -->

                <div class="form-group">

                    <label for="date_prochaine_maintenance">
                        Prochaine maintenance
                    </label>

                    <input
                        type="date"
                        name="date_prochaine_maintenance"
                        id="date_prochaine_maintenance"
                        class="ibs-input"
                        value="<?= $this->escapeHtmlAttr(
                            $equipement[
                                'date_prochaine_maintenance'
                            ] ?? ''
                        ) ?>"
                    >

                </div>

            </div>


            <div class="form-actions">

                <a
                    href="<?= $this->url('equipement') ?>"
                    class="btn-secondary"
                >
                    Annuler
                </a>

                <button
                    type="submit"
                    class="btn-ibs"
                >
                    Enregistrer les modifications
                </button>

            </div>

        </form>

    </div>

</div>
