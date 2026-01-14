-- Copyright (C) 2018 		Netlogic			<dolibarr@netlogic.fr>
-- Copyright (C) 2022 		Éric Seigne			<eric.seigne@cap-rel.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

-- config
DELETE FROM llx_uptosign_uptosignconfig WHERE import_key = 'initial-setup' AND entity='__ENTITY__';

-- propal

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'CustomerSign','sign',NOW(),1,1,'initial-setup',1,'propal:cyan','120,240','-1','120,264','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'CustomerSign','sign',NOW(),1,1,'initial-setup',1,'propal:azur','120,240','-1','120,264','1');

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'propal:cyan','',NULL,'82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'propal:azur','',NULL,'82,14','1');

-- commandes

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'CustomerSign','sign',NOW(),1,1,'initial-setup',1,'order:einstein','120,250','-1','60,250','1');

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'order:einstein','',NULL,'82,14','1');

-- fiche inter

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'CustomerSign','sign',NOW(),1,1,'initial-setup',1,'fichinter:soleil','115,233','-1','82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'VendorSign','sign',NOW(),1,1,'initial-setup',1,'fichinter:soleil','25,233','-1','82,14','1');

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'fichinter:soleil','',NULL,'82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'fichinter:soleil','',NULL,'82,14','1');

-- expeditions differents formats

INSERT INTO llx_uptosign_uptosignconfig (entity, label,sign_or_seal, date_creation, fk_user_creat, fk_user_modif, import_key, status, model_pdf,  sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__, 'CustomerSign','sign',NOW(),1,1,'initial-setup',0,'expedition:rouget','10,263','-1','82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity, label,sign_or_seal, date_creation, fk_user_creat, fk_user_modif, import_key, status, model_pdf,  sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__, 'CustomerSign','sign',NOW(),1,1,'initial-setup',0,'expedition:merou','140,259','-1','82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity, label,sign_or_seal, date_creation, fk_user_creat, fk_user_modif, import_key, status, model_pdf,  sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__, 'CustomerSign','sign',NOW(),1,1,'initial-setup',0,'expedition:espadon','10,263','-1','82,14','1');

INSERT INTO llx_uptosign_uptosignconfig (entity, label,sign_or_seal, date_creation, fk_user_creat, fk_user_modif, import_key, status, model_pdf,  sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__, 'DocumentSeal','seal',NOW(),1,1,'initial-setup',0,'expedition:rouget','',NULL,'82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity, label,sign_or_seal, date_creation, fk_user_creat, fk_user_modif, import_key, status, model_pdf,  sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__, 'DocumentSeal','seal',NOW(),1,1,'initial-setup',0,'expedition:merou','',NULL,'82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity, label,sign_or_seal, date_creation, fk_user_creat, fk_user_modif, import_key, status, model_pdf,  sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__, 'DocumentSeal','seal',NOW(),1,1,'initial-setup',0,'expedition:espadon','',NULL,'82,14','1');


-- contrats

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'CustomerSign','sign',NOW(),1,1,'initial-setup',1,'contrat:strato','115,233','-1','82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'VendorSign','sign',NOW(),1,1,'initial-setup',1,'contrat:strato','25,233','-1','82,14','1');

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'contrat:strato','',NULL,'82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'contrat:strato','',NULL,'82,14','1');


-- factures
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'SealInvoice','sign',NOW(),1,1,'initial-setup',1,'invoice:crabe','25,233','-1','82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'SealInvoice','sign',NOW(),1,1,'initial-setup',1,'invoice:galathea','25,233','-1','82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'SealInvoice','sign',NOW(),1,1,'initial-setup',1,'invoice:homard','25,233','-1','82,14','1');

INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'invoice:crabe','',NULL,'82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'invoice:galathea','',NULL,'82,14','1');
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'DocumentSeal','seal',NOW(),1,1,'initial-setup',1,'invoice:homard','',NULL,'82,14','1');


-- mandat sepa
INSERT INTO llx_uptosign_uptosignconfig (entity,label,sign_or_seal,date_creation,fk_user_creat,fk_user_modif,import_key,status,model_pdf,sign_coordinate,page_sign,seal_coordinate,page_seal)
    VALUES (__ENTITY__,'CustomerSign','sign',NOW(),1,1,'initial-setup',1,'sepamandate:sepamandate_stancer','120,251','-1','120,221','1');


-- email templates

-- remove templates not modifyied by user (datec == tms)

DELETE FROM llx_c_email_templates WHERE module='uptosign' AND datec=tms;

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','propal_send','fr_FR', 0,NULL, NOW(),
        'UptoSign devis',
        1,1,1,
        'Votre devis référence __REF__',
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, le __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Bonjour,<br />vous trouverez ci-joint le devis r&eacute;f&eacute;rence __REF__ pour un montant de __AMOUNT_EXCL_TAX_FORMATED__ HT.<br /><br />N&#39;h&eacute;sitez-pas &agrave; revenir vers nous (par r&eacute;ponse de mail) si vous avez la moindre question ou si vous remarquez une erreur dans ce document.<br /><br />&Eacute;conomisons du papier et du temps, si vous validez ce devis merci de cliquer sur le lien suivant pour proc&eacute;der &agrave; sa <a href="__ONLINE_SIGN_URL__">signature &eacute;lectronique en ligne</a>.<br /><br />Cordialement,<br />--<br />__USER_SIGNATURE__');

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','propal_send','en_US',0,NULL,NOW(),
        'UptoSign proposal',
        5,1,1,
        'Request to sign the proposal ref __REF__',
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, on __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Hello,<br />you will find attached the quote reference __REF__ for an amount of __AMOUNT_EXCL_TAX_FORMATED__ excluding VAT.<br /><br />Do not hesitate to come back to us (by email) if you have any questions or if you notice an error in this document.<br /><br />Let&#39;s save paper and time, if you validate this quote, please click on the following link to proceed with its <a href="__ONLINE_SIGN_URL__">electronic signature online</a>.<br /><br />Cordially,<br />--<br />__MYCOMPANY_NAME__<br />__MYCOMPANY_EMAIL__');

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','fichinter_send','fr_FR',0,NULL,NOW(),
        'UptoSign intervention',
        3,1,1,
        'Intervention sur site de __MYCOMPANY_NAME__',
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, le __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Bonjour,<br />vous trouverez ci-joint le document concernant notre intervention r&eacute;f&eacute;rence __REF__ .<br /><br />N&#39;h&eacute;sitez-pas &agrave; revenir vers nous (par r&eacute;ponse de mail) si vous avez la moindre question ou si vous remarquez une erreur dans ce document.<br /><br />&Eacute;conomisons du papier et du temps, merci de cliquer sur le lien suivant pour proc&eacute;der &agrave; sa <a href="__ONLINE_SIGN_URL__">signature &eacute;lectronique en ligne</a>.<br /><br />Cordialement,<br />--<br />__USER_SIGNATURE__');

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','fichinter_send','en_US',0,NULL,NOW(),
        'UptoSign intervention',
        7,1,1,
        'Request to sign the intervention of company __MYCOMPANY_NAME__',
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, on __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Hello,<br />you will find attached the document reference __REF__ for our intervention.<br /><br />Do not hesitate to come back to us (by email) if you have any questions or if you notice an error in this document.<br /><br />Let&#39;s save paper and time, please click on the following link to proceed with its <a href="__ONLINE_SIGN_URL__">electronic signature online</a>.<br /><br />Cordially,<br />--<br />__MYCOMPANY_NAME__<br />__MYCOMPANY_EMAIL__');

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','order_send','fr_FR',0,NULL,NOW(),
        'UptoSign commande',
        9,1,1,
        'Votre commande référence __REF__',
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, le __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Bonjour,<br />vous trouverez ci-joint votre commande r&eacute;f&eacute;rence __REF__ pour un montant de __AMOUNT_EXCL_TAX_FORMATED__ HT.<br /><br />N&#39;h&eacute;sitez-pas &agrave; revenir vers nous (par r&eacute;ponse de mail) si vous avez la moindre question ou si vous remarquez une erreur dans ce document.<br /><br />&Eacute;conomisons du papier et du temps, pour valider cette commande merci de cliquer sur le lien suivant pour proc&eacute;der &agrave; sa <a href="__ONLINE_SIGN_URL__">signature &eacute;lectronique en ligne</a>.<br /><br />Cordialement,<br />--<br />__USER_SIGNATURE__');

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','order_send','en_US',0,NULL,NOW(),
        'UptoSign order',
        11,1,1,
        'Request to sign the order __REF__',
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, on __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Hello,<br />you will find attached the document reference __REF__ for your order.<br /><br />Do not hesitate to come back to us (by email) if you have any questions or if you notice an error in this document.<br /><br />Let&#39;s save paper and time, please click on the following link to proceed with its <a href="__ONLINE_SIGN_URL__">electronic signature online</a>.<br /><br />Cordially,<br />--<br />__MYCOMPANY_NAME__<br />__MYCOMPANY_EMAIL__');

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','contract','fr_FR',0,NULL,NOW(),
        'UptoSign contrat',
        1,1,1,
        "Signature du contrat __REF__",
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, le __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Bonjour,<br />vous trouverez ci-joint le contrat r&eacute;f&eacute;rence __REF__<br /><br />N&#39;h&eacute;sitez-pas &agrave; revenir vers nous (par r&eacute;ponse de mail) si vous avez la moindre question ou si vous remarquez une erreur dans ce document.<br /><br />&Eacute;conomisons du papier et du temps, pour valider ce contrat merci de cliquer sur le lien suivant pour proc&eacute;der &agrave; sa <a href="__ONLINE_SIGN_URL__">signature &eacute;lectronique en ligne</a>.<br /><br />Cordialement,<br />--<br />__USER_SIGNATURE__');

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','contract','en_US',0,NULL,NOW(),
        'UptoSign contract',
        5,1,1,
        'Request to sign the contract __REF__',
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, on __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Hello,<br />you will find attached the document reference __REF__ for our contract.<br /><br />Do not hesitate to come back to us (by email) if you have any questions or if you notice an error in this document.<br /><br />Let&#39;s save paper and time, please click on the following link to proceed with its <a href="__ONLINE_SIGN_URL__">electronic signature online</a>.<br /><br />Cordially,<br />--<br />__MYCOMPANY_NAME__<br />__MYCOMPANY_EMAIL__');

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,joinfiles,topic,content)
    VALUES (
        1,'uptosign','uptosign_init_expedition','fr_FR',0,NULL,NOW(),
        'UptoSign expedition',
        9,1,1,
        'Bordereau d''expédition référence __REF__',
        '__MYCOMPANY_NAME__<br />__MYCOMPANY_ADDRESS__<br />__MYCOMPANY_ZIP__ __MYCOMPANY_TOWN__<br /><br />__MYCOMPANY_TOWN__, le __DAY_TEXT__ __DAY__ __MONTH_TEXT__ __YEAR__<br /><br />Bonjour,<br />vous trouverez ci-joint le bordereau d''exp&eacute;dition r&eacute;f&eacute;rence __REF__<br /><br />N&#39;h&eacute;sitez-pas &agrave; revenir vers nous (par r&eacute;ponse de mail) si vous avez la moindre question ou si vous remarquez une erreur dans ce document.<br /><br />&Eacute;conomisons du papier et du temps, merci de cliquer sur le lien suivant pour proc&eacute;der &agrave; sa <a href="__ONLINE_SIGN_URL__">signature &eacute;lectronique en ligne</a>.<br /><br />Cordialement,<br />--<br />__USER_SIGNATURE__');


-- action comm

-- INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, color, picto, position)
--     VALUES (471040, 'AC_UPTOSIGN', 'module', 'UptoSignChoosesign', 'uptosign@uptosign', 1, NULL, NULL, 'signature', 471040)
--     ON DUPLICATE KEY UPDATE id='471040',code='AC_UPTOSIGN', type='module', libelle='UptoSignChoosesign', module='uptosign@uptosign', position='471040',picto='signature';

-- INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, color, picto, position)
--     VALUES (471041, 'AC_UPTOSEAL', 'module', 'UptoSignChooseseal', 'uptosign@uptosign', 1, NULL, NULL, 'stamp', 471041)
--     ON DUPLICATE KEY UPDATE id='471041',code='AC_UPTOSEAL', type='module', libelle='UptoSignChooseseal', module='uptosign@uptosign',position='471041',picto='stamp';

-- contact type

INSERT INTO `llx_c_type_contact` (`rowid`, `element`, `source`, `code`, `libelle`, `active`, `module`, `position`)
    VALUES ('205', 'commande', 'external', 'BONEXPEDITION', 'Contact bon de livraison', '1', NULL, '0');



-- Add new contacts for signing dolibarr documents -- exter people

-- INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
--     VALUES(10001,'agenda','external','CustomerSign','Signature électronique (vendeur)','1');
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10002,'commande','external','CustomerSign','Signature électronique (client)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10003,'conferenceorbooth','external','CustomerSign','Signature électronique (client)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10004,'contrat','external','CustomerSign','Signature électronique (client)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';
-- INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
--     VALUES(10005,'dolresource','external','CustomerSign','Signature électronique (client)','1','uptosign');
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10006,'facture','external','CustomerSign','Signature électronique (client)','1','uptosign')
        ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10007,'fichinter','external','CustomerSign','Signature électronique (client)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';
-- INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
--     VALUES(10008,'invoice_supplier','external','CustomerSign','Signature électronique (client)','1','uptosign');
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10009,'order_supplier','external','CustomerSign','Signature électronique (client)','1','uptosign');
INSERT INTO llx_c_type_contact (element,source,code,libelle,active,module)
    VALUES('order_supplier','external','VendorSign','Signature électronique (vendeur/fournisseur)','1','uptosign');
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10010,'project','external','CustomerSign','Signature électronique (client)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10011,'project_task','external','CustomerSign','Signature électronique (client)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10012,'propal','external','CustomerSign','Signature électronique (client)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10013,'supplier_proposal','external','VendorSign','Signature électronique (fournisseur/vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10014,'ticket','external','CustomerSign','Signature électronique (client)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';


-- There is no sepamandate in dolibarr bases objects like dolresource
-- INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
--     VALUES(10015,'sepamandate','external','CustomerSign','Signature électronique (client)','1','uptosign')
--     ON DUPLICATE KEY UPDATE libelle='Signature électronique (client)', module='uptosign', code='CustomerSign';

-- Add new contacts for signing dolibarr documents -- intern people

-- INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
--     VALUES(10021,'agenda','internal','VendorSign','Signature électronique (client)','1','uptosign');
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10022,'commande','internal','VendorSign','Signature électronique (vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10023,'conferenceorbooth','internal','VendorSign','Signature électronique (vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10024,'contrat','internal','VendorSign','Signature électronique (vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
-- INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
--     VALUES(10025,'dolresource','internal','VendorSign','Signature électronique (vendeur)','1','uptosign');
-- INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
--     VALUES(10026,'facture','internal','VendorSign','Signature électronique (vendeur)','1','uptosign');
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10027,'fichinter','internal','VendorSign','Signature électronique (vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
-- INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
--     VALUES(10028,'invoice_supplier','internal','VendorSign','Signature électronique (vendeur)','1','uptosign');
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10029,'order_supplier','internal','VendorSign','Signature électronique (fournisseur/vendeur)','1','uptosign');
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10030,'project','internal','VendorSign','Signature électronique (vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10031,'project_task','internal','VendorSign','Signature électronique (vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10032,'propal','internal','VendorSign','Signature électronique (vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10033,'supplier_proposal','internal','VendorSign','Signature électronique (fournisseur/vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';
INSERT INTO llx_c_type_contact (rowid,element,source,code,libelle,active,module)
    VALUES(10034,'ticket','internal','VendorSign','Signature électronique (vendeur)','1','uptosign')
    ON DUPLICATE KEY UPDATE libelle='Signature électronique (vendeur)', module='uptosign', code='VendorSign';



-- digital sign dictionary

insert into llx_c_digitalsign (rowid, code, label, active, module) values (1, 'dolibarr',  'DolibarrNative', 1, 'core');
insert into llx_c_digitalsign (rowid, code, label, active, module) values (2, 'uptosign',  'UpToSignCertified', 1, 'uptosign');
