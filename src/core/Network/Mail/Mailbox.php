<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero
 * de GNU publicada por la Fundación para el Software Libre, ya sea la
 * versión 3 de la Licencia, o (a su elección) cualquier versión
 * posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU
 * para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

use PhpImap\Mailbox;
use PhpImap\IncomingMailHeader;
use PhpImap\IncomingMailAttachment;

/**
 * Casilla de correo electrónico que se usará con el servicio 'mail_receiver'.
 */
class Network_Mail_Mailbox extends Mailbox
{
    /**
     * Verifica si se está conectado al servidor IMAP.
     *
     * @return bool true si se está conectado, false en caso contrario.
     */
    public function isConnected(): bool
    {
        return $this->getImapStream() !== false;
    }

    /**
     * Obtiene el estado de la casilla de correo.
     *
     * @param string|null $folder Carpeta a consultar, `null` para la actual.
     * @return \stdClass Objeto con el estado de la casilla de correo.
     */
    public function status(?string $folder = null): \stdClass
    {
        $originalMailbox = $this->getImapPath();

        if ($folder !== null) {
            $this->switchMailbox($folder);
        }

        $status = $this->statusMailbox();

        if ($folder !== null) {
            $this->switchMailbox($originalMailbox);
        }

        return $status;
    }

    /**
     * Cuenta la cantidad de mensajes sin leer en la casilla de correo.
     *
     * @param string|null $folder Carpeta a consultar, `null` para la actual.
     * @return int Cantidad de mensajes sin leer.
     */
    public function countUnreadMails(?string $folder = null): int
    {
        $status = $this->status($folder);

        return $status->unseen ?? 0;
    }

    /**
     * Obtiene un mensaje desde la casilla de correo.
     *
     * @param int $mailId UID del mensaje.
     * @param array $filter Filtros a usar para las partes del mensaje.
     * @return array Arreglo con los datos del mensaje.
     */
    public function getMailAsArray(int $mailId, array $filters = []): array
    {
        $mail = $this->getMail($mailId, false);
        $message = [
            'uid' => $mailId,
            'date' => $mail->date,
            'charset' => $mail->charset,
            'header' => $mail->headersRaw,
            'body' => [
                'plain' => $mail->textPlain,
                'html' => $mail->textHtml
            ],
            'attachments' => [],
        ];

        // Procesar los filtros para las partes del mensaje
        foreach ($mail->getAttachments() as $attachment) {
            if (!$filters || $this->applyFilter($attachment, $filters)) {
                $message['attachments'][] = [
                    'name' => $attachment->name,
                    'data' => $attachment->getContents(),
                    'size' => $attachment->size,
                    'type' => $attachment->mime
                ];
            }
        }

        return $message;
    }

    /**
     * Aplica los filtros a una parte del mensaje.
     *
     * @param \PhpImap\IncomingMailAttachment $attachment Parte del mensaje a
     * filtrar.
     * @param array $filters Filtros a usar.
     * @return bool true si la parte del mensaje pasa los filtros, `false` en
     * caso contrario.
     */
    protected function applyFilter(
        IncomingMailAttachment $attachment,
        array $filters
    ): bool
    {
        // Filtrar por: subtype.
        if (!empty($filters['subtype'])) {
            $subtype = strtoupper($attachment->subtype);
            $subtypes = array_map('strtoupper', $filters['subtype']);
            if (!in_array($subtype, $subtypes)) {
                return false;
            }
        }

        // Filtrar por: extension.
        if (!empty($filters['extension'])) {
            $extension = strtolower(pathinfo($attachment->name, PATHINFO_EXTENSION));
            $extensions = array_map('strtolower', $filters['extension']);
            if (!in_array($extension, $extensions)) {
                return false;
            }
        }

        // Pasó los filtros ok.
        return true;
    }

    /**
     * Obtiene el número de mensaje a partir del UID.
     *
     * @param int $uid UID del mensaje.
     * @return int Número del mensaje.
     */
    public function getMsgNumber($uid): int
    {
        $imapStream = $this->getImapStream();
        $messageNumber = imap_msgno($imapStream, $uid);

        if (!$messageNumber) {
            throw new \RuntimeException(__(
                'No se pudo obtener el número del mensaje para el UID proporcionado.'
            ));
        }

        return $messageNumber;
    }

    /**
     * Comprueba la casilla de correo.
     *
     * @return array Arreglo con los datos de la casilla de correo.
     * @deprecated Utilizar checkMailbox().
     */
    public function check(): array
    {
        return (array) $this->checkMailbox();
    }

    /**
     * Cuenta la cantidad de mensajes en la casilla de correo.
     *
     * @return int Cantidad de mensajes en la casilla (leídos y no leídos).
     * @deprecated Utilizar countMails().
     */
    public function countMessages(): int
    {
        return $this->countMails();
    }

    /**
     * Cuenta la cantidad de mensajes sin leer en la casilla de correo.
     *
     * @param string|null $folder Carpeta a consultar, `null` para la actual.
     * @return int Cantidad de mensajes sin leer.
     * @deprecated Utilizar countUnreadMails().
     */
    public function countUnreadMessages(?string $folder = null): int
    {
        return $this->countUnreadMails($folder);
    }

    /**
     * Realiza una búsqueda en la casilla de correo.
     *
     * @param string $criteria Criterios de búsqueda.
     * @return array Arreglo con los UIDs de los mensajes que coinciden con el filtro.
     * @deprecated Utilizar searchMailbox().
     */
    public function search(string $criteria = 'UNSEEN'): array
    {
        return $this->searchMailbox($criteria);
    }

    /**
     * Obtiene la información de la cabecera del mensaje.
     *
     * @param int $mailId UID del mensaje.
     * @return \PhpImap\IncomingMailHeader Información de la cabecera del mensaje.
     * @deprecated Utilizar getMailHeader().
     */
    public function getHeaderInfo(int $mailId): IncomingMailHeader
    {
        return $this->getMailHeader($mailId);
    }

    /**
     * Obtiene un mensaje desde la casilla de correo.
     *
     * @param int $mailId UID del mensaje.
     * @param array $filters Filtros a usar para las partes del mensaje.
     * @return array Arreglo con los datos del mensaje.
     * @deprecated Utilizar getMailAsArray().
     */
    public function getMessage(int $mailId, array $filters = []): array
    {
        return $this->getMailAsArray($mailId, $filters);
    }

    /**
     * Marca un mensaje como leído.
     *
     * @param int $mailId UID del mensaje.
     * @deprecated Utilizar markMailAsRead().
     */
    public function setSeen(int $mailId): void
    {
        $this->markMailAsRead($mailId);
    }

    /**
     * Elimina un mensaje de la casilla de correo.
     *
     * @param int $mailId UID del mensaje.
     * @return void
     * @deprecated Utilizar deleteMail().
     */
    public function delete(int $mailId): void
    {
        $this->deleteMail($mailId);
    }
}
