DROP INDEX uk_uptosign_uptosignconfig_model_sos ON llx_uptosign_uptosignconfig;
ALTER TABLE llx_uptosign_uptosignconfig ADD UNIQUE INDEX uk_uptosign_uptosignconfig_model_sos(entity, model_pdf, sign_or_seal, label);
