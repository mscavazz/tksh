<?php
class Tokenshop_MetamaskconnectAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');

        $action = Tools::getValue('action', 'save');

        $context = Context::getContext();
        $id_customer = (int)$context->customer->id;

        if ($action === 'get') {
            if (!$id_customer) {
                echo json_encode(['success' => false, 'error' => 'Client non connecté']);
                exit;
            }
            $row = Db::getInstance()->getRow('SELECT wallet_address, network, label, date_add FROM `' . _DB_PREFIX_ . 'tokenshop_metamaskconnect` WHERE id_customer = ' . (int)$id_customer);
            if ($row) {
                echo json_encode(['success' => true, 'wallet' => $row]);
            } else {
                echo json_encode(['success' => true, 'wallet' => null]);
            }
            exit;
        }

        if ($action === 'delete') {
            if (!$id_customer) {
                echo json_encode(['success' => false, 'error' => 'Client non connecté']);
                exit;
            }
            $table = _DB_PREFIX_ . 'tokenshop_metamaskconnect';
            $deleted = Db::getInstance()->execute('DELETE FROM `' . $table . '` WHERE id_customer = ' . (int)$id_customer);
            if ($deleted) {
                echo json_encode(['success' => true, 'action' => 'deleted']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur suppression']);
            }
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $wallet = isset($input['wallet_address']) ? pSQL($input['wallet_address']) : '';
        $network = isset($input['network']) ? pSQL($input['network']) : null;
        $label = isset($input['label']) ? pSQL($input['label']) : null;

        if (!$id_customer) {
            echo json_encode(['success' => false, 'error' => 'Client non connecté']);
            exit;
        }

        if (empty($wallet) || !preg_match('/^0x[a-fA-F0-9]{40}$/', $wallet)) {
            echo json_encode(['success' => false, 'error' => 'Adresse wallet invalide']);
            exit;
        }

        if ($network !== null && (int)$network !== 137) {
            echo json_encode(['success' => false, 'error' => 'Seul le réseau Polygon (137) est autorisé']);
            exit;
        }

        $table = _DB_PREFIX_ . 'tokenshop_metamaskconnect';

        $owner = Db::getInstance()->getRow('SELECT id_customer FROM `' . $table . '` WHERE wallet_address = "' . pSQL($wallet) . '"');
        if ($owner && isset($owner['id_customer']) && (int)$owner['id_customer'] !== $id_customer) {
            echo json_encode(['success' => false, 'error' => 'Cette adresse est déjà liée à un autre compte client.']);
            exit;
        }

        $existing = Db::getInstance()->getRow('SELECT id_wallet FROM `' . $table . '` WHERE id_customer = ' . (int)$id_customer);

        if ($existing && isset($existing['id_wallet'])) {
            $data = array('wallet_address' => pSQL($wallet), 'date_add' => date('Y-m-d H:i:s'));
            if ($label !== null) $data['label'] = $label;
            if ($network !== null) $data['network'] = $network;
            Db::getInstance()->update('tokenshop_metamaskconnect', $data, 'id_wallet=' . (int)$existing['id_wallet']);
            echo json_encode(['success' => true, 'action' => 'updated']);
            exit;
        }

        $inserted = Db::getInstance()->insert('tokenshop_metamaskconnect', array(
            'id_customer' => (int)$id_customer,
            'wallet_address' => pSQL($wallet),
            'network' => pSQL($network),
            'label' => pSQL($label),
            'date_add' => date('Y-m-d H:i:s')
        ));

        if ($inserted) {
            echo json_encode(['success' => true, 'action' => 'inserted']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
        }
        exit;
    }
}
