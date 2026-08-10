<?php

declare(strict_types=1);

/** Generell funktions */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\ContactSensor\DebugHelper;
use Wilkware\ContactSensor\VersionHelper;

/**
 * CLASS ContactSensor
 */
class ContactSensor extends IPSModuleStrict
{
    // -------------------------------------------------------------------------
    // Traits
    // -------------------------------------------------------------------------

    use DebugHelper;
    use VersionHelper;

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        // Contact state variables
        $this->RegisterPropertyInteger('StateVariable', 0);
        $this->RegisterPropertyInteger('StateVariable2', 0);
        $this->RegisterPropertyInteger('StateVariable3', 0);
        $this->RegisterPropertyInteger('StateVariable4', 0);

        // Decrease variables
        $this->RegisterPropertyInteger('Delay', 30);
        $this->RegisterPropertyBoolean('OpenValve', false);
        $this->RegisterPropertyInteger('Level', 0);
        $this->RegisterPropertyBoolean('TempDiff', false);
        $this->RegisterPropertyInteger('Difference', 10);
        $this->RegisterPropertyBoolean('RepeatCheck', false);
        $this->RegisterPropertyInteger('RepeatTime', 1);
        $this->RegisterPropertyBoolean('SwitchBack', false);
        $this->RegisterPropertyInteger('SwitchTime', 60);

        // Radiator variables
        $this->RegisterPropertyInteger('Radiator1', 0);
        $this->RegisterPropertyInteger('Radiator2', 0);
        $this->RegisterPropertyInteger('ExecScript', 0);

        // Clima variables
        $this->RegisterPropertyInteger('TempIndoor', 0);
        $this->RegisterPropertyInteger('TempOutdoor', 0);

        // Dashboard variables
        $this->RegisterPropertyInteger('DashboardMessage', 0);
        $this->RegisterPropertyInteger('DashboardTrigger', 3);
        $this->RegisterPropertyInteger('DashboardOpening', 0);
        $this->RegisterPropertyInteger('DashboardClosing', 0);
        $this->RegisterPropertyInteger('NotificationMessage', 0);
        $this->RegisterPropertyInteger('NotificationTrigger', 3);
        $this->RegisterPropertyString('RoomName', $this->Translate('Unknown'));
        $this->RegisterPropertyString('TextOpening', $this->Translate('%R: Temperature is lowered!'));
        $this->RegisterPropertyString('TextClosing', $this->Translate('%R: Temperature reduction cancelled!'));
        $this->RegisterPropertyString('TitleMessage', $this->Translate('Contact Sensor'));
        $this->RegisterPropertyInteger('InstanceWebfront', 0);
        $this->RegisterPropertyInteger('ScriptMessage', 0);

        // Delay trigger
        $this->RegisterTimer('DelayTrigger', 0, "IPS_RequestAction(\$_IPS['TARGET'],'Delay', 0);");
        
        // Repeat trigger
        $this->RegisterTimer('RepeatTrigger', 0, "IPS_RequestAction(\$_IPS['TARGET'],'Repeat', 0);");
        
        // Switch trigger
        $this->RegisterTimer('SwitchTrigger', 0, "IPS_RequestAction(\$_IPS['TARGET'],'Switch', 0);");

        // Internal state
        $this->RegisterAttributeBoolean('Reduction', false);
        $this->RegisterAttributeInteger('Sensors', 0);
        $this->RegisterAttributeInteger('Message', 0);
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting IP-Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        parent::Destroy();
    }

    /**
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Delete all references in order to readd them
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all registrations in order to readd them
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        //Register references
        $variable = $this->ReadPropertyInteger('StateVariable');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('StateVariable2');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('StateVariable3');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('StateVariable4');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('Level');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('Radiator1');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('Radiator2');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('TempIndoor');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $variable = $this->ReadPropertyInteger('TempOutdoor');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }
        $script = $this->ReadPropertyInteger('ExecScript');
        if (IPS_ScriptExists($script)) {
            $this->RegisterReference($script);
        }
        $script = $this->ReadPropertyInteger('ScriptMessage');
        if (IPS_ScriptExists($script)) {
            $this->RegisterReference($script);
        }
        $instance = $this->ReadPropertyInteger('InstanceWebfront');
        if (IPS_InstanceExists($instance)) {
            $this->RegisterReference($instance);
        }

        //Register update messages = Create our trigger
        if (IPS_VariableExists($this->ReadPropertyInteger('StateVariable'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('StateVariable'), VM_UPDATE);
        }
        if (IPS_VariableExists($this->ReadPropertyInteger('StateVariable2'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('StateVariable2'), VM_UPDATE);
        }
        if (IPS_VariableExists($this->ReadPropertyInteger('StateVariable3'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('StateVariable3'), VM_UPDATE);
        }
        if (IPS_VariableExists($this->ReadPropertyInteger('StateVariable3'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('StateVariable3'), VM_UPDATE);
        }
        // Set Internal State
        $this->InternalState();
    }

    /**
     * The content of the function can be overwritten in order to carry out own reactions to certain messages.
     * The function is only called for registered MessageIDs/SenderIDs combinations.
     *
     * data[0] = new value
     * data[1] = value changed?
     * data[2] = old value
     * data[3] = timestamp.
     *
     * @param int   $timestamp Continuous counter timestamp
     * @param int   $sender    Sender ID
     * @param int   $message   ID of the message
     * @param array{0:mixed,1:bool,2:mixed,3:int} $data Data of the message
     *
     * @return void
     */
    public function MessageSink(int $timestamp, int $sender, int $message, array $data): void
    {
        // $this->LogDebug(__FUNCTION__, 'SenderId: '. $senderID . ' Data: ' . print_r($data, true), 0);
        switch ($message) {
            case VM_UPDATE:
                if ($sender == $this->ReadPropertyInteger('StateVariable')) {
                    $this->LogDebug(__FUNCTION__, 'Kontaktsender 1: #' . $sender . ', New: ' . $data[0] . ', Changed: ' . var_export($data[1], true) . ', Old: ' . $data[2]);
                    // state changes ?
                    if ($data[0] == 1 && $data[1] == true) { // state on 1, i.e. OPEN
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 1: State auf <OPEN> geschalten');
                        $this->Open(1);
                    } elseif ($data[0] == 0 && $data[1] == true) { // state on 0, i.e. CLOSE
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 1: State auf <CLOSE> geschalten');
                        $this->Close(1);
                    } else { // OnChange - no state changes
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 1: State unveraendert - keine Zustandsänderung');
                    }
                } elseif ($sender == $this->ReadPropertyInteger('StateVariable2')) {
                    $this->LogDebug(__FUNCTION__, 'Kontaktsender 2: #' . $sender . ', New: ' . $data[0] . ', Changed: ' . var_export($data[1], true) . ', Old: ' . $data[2]);
                    // state changes ?
                    if ($data[0] == 1 && $data[1] == true) { // state on 1, i.e. OPEN
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 2: State auf <OPEN> geschalten');
                        $this->Open(2);
                    } elseif ($data[0] == 0 && $data[1] == true) { // state on 0, i.e. CLOSE
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 2: State auf <CLOSE> geschalten');
                        $this->Close(2);
                    } else { //OnChange - no state changes
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 2: State unveraendert - keine Zustandsänderung');
                    }
                } elseif ($sender == $this->ReadPropertyInteger('StateVariable3')) {
                    $this->LogDebug(__FUNCTION__, 'Kontaktsender 3: #' . $sender . ', New: ' . $data[0] . ', Changed: ' . var_export($data[1], true) . ', Old: ' . $data[2]);
                    // state changes ?
                    if ($data[0] == 1 && $data[1] == true) { // state on 1, i.e. OPEN
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 3: State auf <OPEN> geschalten');
                        $this->Open(4);
                    } elseif ($data[0] == 0 && $data[1] == true) { // state on 0, i.e. CLOSE
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 3: State auf <CLOSE> geschalten');
                        $this->Close(4);
                    } else { // OnChange - no state changes
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 3: State unveraendert - keine Zustandsänderung');
                    }
                } elseif ($sender == $this->ReadPropertyInteger('StateVariable4')) {
                    $this->LogDebug(__FUNCTION__, 'Kontaktsender 4: #' . $sender . ', New: ' . $data[0] . ', Changed: ' . var_export($data[1], true) . ', Old: ' . $data[2]);
                    // state changes ?
                    if ($data[0] == 1 && $data[1] == true) { // state on 1, i.e. OPEN
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 4: State auf <OPEN> geschalten');
                        $this->Open(8);
                    } elseif ($data[0] == 0 && $data[1] == true) { // state on 0, i.e. CLOSE
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 4: State auf <CLOSE> geschalten');
                        $this->Close(8);
                    } else { // OnChange - no state changes
                        $this->LogDebug(__FUNCTION__, 'Kontaktsender 4: State unveraendert - keine Zustandsänderung');
                    }
                } else {
                    $this->LogDebug(__FUNCTION__, 'Kontaktsender: #' . $sender . ' unbekannt!');
                }
                break;
        }
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     *
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, $ident . ' Timer wurde ausgelöst!');
        switch ($ident) {
            case 'Delay':
                $this->Decrease();
                break;
            case 'Repeat':
                $this->Decrease();
                break;
            case 'Switch':
                $this->Restore();
                break;
        }
    }

    /**
     * Open - Executes if state is OPEN.
     *
     * @param Integer $sensor Id of the triggered sensor
     *
     * @return void
     */
    private function Open($sensor): void
    {
        // (1) push sensor to process
        $sensors = $this->ReadAttributeInteger('Sensors');
        $sensors = $sensors ^ $sensor;
        $this->WriteAttributeInteger('Sensors', $sensors);

        // (2) check timer or active reduction
        $delay = $this->ReadPropertyInteger('Delay');
        if ((($delay > 0) && ($this->GetTimerInterval('DelayTrigger') != 0)) || $this->ReadAttributeBoolean('Reduction')) {
            $this->LogDebug(__FUNCTION__, 'Kontaktsender ' . $sensor . ' wollte auslösen, aber ein anderer Sensor war schneller!');
            return;
        }

        // (3) start action
        $this->LogDebug(__FUNCTION__, 'Kontaktsender ' . $sensor . ' hat Prozess ausgelöst!');
        if ($delay > 0) {
            $this->SetTimerInterval('DelayTrigger', 1000 * $delay);
        } else {
            $this->Decrease();
        }
    }

    /**
     * Close - Executes if state is CLOSE.
     *
     * @param int $sensor Id of the triggered sensor
     *
     * @return void
     */
    private function Close($sensor): void
    {
        // (1) pop sensor from process
        $sensors = $this->ReadAttributeInteger('Sensors');
        $sensors = $sensors ^ $sensor;
        $this->WriteAttributeInteger('Sensors', $sensors);

        // (2) check if some sensor still in process
        if ($sensors > 0) {
            $this->LogDebug(__FUNCTION__, 'Kontaktsender ' . $sensor . ' wollte aufheben, aber ein anderer Sensor ist noch offen!');
            return;
        }

        // (3) end action
        $this->LogDebug(__FUNCTION__, 'Kontaktsender ' . $sensor . ' hat Prozess beendet!');
        $this->Restore();
    }

    /**
     * Decrease - decrease th heater temperature.
     *
     * @return void
     */
    private function Decrease(): void
    {
        $this->LogDebug(__FUNCTION__, 'Funktion wurde aufgerufen!');
        // deactivate timer
        $this->SetTimerInterval('DelayTrigger', 0);
        $this->SetTimerInterval('RepeatTrigger', 0);
        $this->SetTimerInterval('SwitchTrigger', 0);

        // conditional switching
        $condition = true;

        // (1) check ventil position
        if ($this->ReadPropertyBoolean('OpenValve')) {
            $lid = $this->ReadPropertyInteger('Level');
            if ($lid != 0) {
                if (GetValue($lid) <= 0) {
                    $this->LogDebug(__FUNCTION__, 'Ventilpostionscheck ist aktiv und traf zu!');
                    $condition = false;
                }
            } else {
                $this->LogDebug(__FUNCTION__, 'Ventilpostionscheck ist aktiv aber keine Positionsvariable hinterlegt!');
            }
        }

        // (2) check temperature diff
        if ($this->ReadPropertyBoolean('TempDiff')) {
            $diff = $this->ReadPropertyInteger('Difference');
            $iid = $this->ReadPropertyInteger('TempIndoor');
            $oid = $this->ReadPropertyInteger('TempOutdoor');
            if (($iid != 0) & ($oid != 0)) {
                if ((GetValue($iid) - GetValue($oid)) < $diff) {
                    $this->LogDebug('Decrease', 'Temperaturcheck ist aktiv und traf zu!');
                    $condition = false;
                }
            } else {
                $this->LogDebug('Decrease', 'Temperaturcheck ist aktiv aber keine Temperaturvariable hinterlegt!');
            }
        }
        if ($condition) {
            // HM 'WINDOW_STATE' set to <OPEN>
            $radiator = $this->ReadPropertyInteger('Radiator1');
            if ($radiator != 0) {
                $ret = HM_WriteValueInteger($radiator, 'WINDOW_STATE', 1);
                $this->LogDebug('Decrease', 'Heizkörper 1: #' . $radiator . ' Fensterstatus auf OPEN setzen => ' . var_export($ret, true));
            }
            $radiator = $this->ReadPropertyInteger('Radiator2');
            if ($radiator != 0) {
                $ret = HM_WriteValueInteger($radiator, 'WINDOW_STATE', 1);
                $this->LogDebug('Decrease', 'Heizkörper 2: #' . $radiator . ' Fensterstatus auf OPEN setzen => ' . var_export($ret, true));
            }
            // Execute script
            $script = $this->ReadPropertyInteger('ExecScript');
            if ($script != 0) {
                if (IPS_ScriptExists($script)) {
                    $rs = IPS_RunScriptEx($script, ['MODUL' => $this->InstanceID, 'WINDOW_STATE' => 1]);
                    $this->LogDebug(__FUNCTION__, 'Script Execute Return Value: ' . $rs);
                } else {
                    $this->LogDebug(__FUNCTION__, 'Script #' . $script . ' does not exist!');
                }
            }
            // Internal State
            $this->WriteAttributeBoolean('Reduction', true);
            // Switch back timer
            if ($this->ReadPropertyBoolean('SwitchBack')) {
                $time = $this->ReadPropertyInteger('SwitchTime');
                $this->SetTimerInterval('SwitchTrigger', $time * 60 * 1000);
            }
            // Send message ?
            $this->SendMessage(true);
        } elseif ($this->ReadPropertyBoolean('RepeatCheck')) {
            $time = $this->ReadPropertyInteger('RepeatTime');
            $this->SetTimerInterval('RepeatTrigger', $time * 60 * 1000);
        }
    }

    /**
     * Restore - set heater back to his programm temperature.
     *
     * @return void
     */
    private function Restore(): void
    {
        $this->LogDebug(__FUNCTION__, 'Funktion wurde aufgerufen!');

        // Active delay timer?
        if ($this->GetTimerInterval('DelayTrigger') > 0) {
            // Timer deactivate
            $this->SetTimerInterval('DelayTrigger', 0);
            $this->LogDebug(__FUNCTION__, 'Ein Verzögerungs-Timer hatte noch nicht ausgelöst!!!');
        }

        // Active repeat timer?
        if ($this->GetTimerInterval('RepeatTrigger') > 0) {
            // Timer deactivate
            $this->SetTimerInterval('RepeatTrigger', 0);
            $this->LogDebug(__FUNCTION__, 'Ein Wiederholungs-Timer war noch aktiv!!!');
        }

        // Active switch back timer?
        if ($this->GetTimerInterval('SwitchTrigger') > 0) {
            // Timer deactivate
            $this->SetTimerInterval('SwitchTrigger', 0);
            $this->LogDebug(__FUNCTION__, 'Ein Aufhebungs-Timer war noch aktiv und hatte noch nicht ausgelöst!!!');
        }

        // was reducted?
        if (!$this->ReadAttributeBoolean('Reduction')) {
            $this->LogDebug(__FUNCTION__, 'Kein Aktion  notwendig, da nicht ausgelöst!');
            return;
        }

        // HM 'WINDOW_STATE' set to <CLOSE>
        $radiator = $this->ReadPropertyInteger('Radiator1');
        if ($radiator != 0) {
            $ret = @HM_WriteValueInteger($radiator, 'WINDOW_STATE', 0);
            if ($ret === false) {
                $this->LogMessage('Error writing WINDOW_STATE #1', KL_ERROR);
            }
            $this->LogDebug(__FUNCTION__, 'Heizkörper 1: #' . $radiator . ' Fensterstatus auf CLOSE setzen => ' . var_export($ret, true));
        }

        // HM 'WINDOW_STATE' set to <CLOSE>
        $radiator = $this->ReadPropertyInteger('Radiator2');
        if ($radiator != 0) {
            $ret = @HM_WriteValueInteger($radiator, 'WINDOW_STATE', 0);
            if ($ret === false) {
                $this->LogMessage('Error writing WINDOW_STATE #2', KL_ERROR);
            }
            $this->LogDebug(__FUNCTION__, 'Heizkörper 2: #' . $radiator . ' Fensterstatus auf CLOSE setzen => ' . var_export($ret, true));
        }

        // Execute script
        $script = $this->ReadPropertyInteger('ExecScript');
        if ($script != 0) {
            if (IPS_ScriptExists($script)) {
                $rs = IPS_RunScriptEx($script, ['MODUL' => $this->InstanceID, 'WINDOW_STATE' => 0]);
                $this->LogDebug(__FUNCTION__, 'Script Execute Return Value: ' . $rs);
            } else {
                $this->LogDebug(__FUNCTION__, 'Script #' . $script . ' does not exist!');
            }
        }

        // Internal State
        $this->WriteAttributeBoolean('Reduction', false);

        // Send Message
        $this->SendMessage(false);
    }

    /**
     * SendMessage - if setuped. its send a message to indicate the state changes
     *
     * @param bool $state contact state (true is open | false is close).
     *
     * @return void
     */
    private function SendMessage(bool $state): void
    {
        $isDashboard = $this->ReadPropertyInteger('DashboardMessage');
        $isNotify = $this->ReadPropertyInteger('NotificationMessage');

        // Check output
        if (!$isDashboard && !$isNotify) {
            // nothing to do
            return;
        }

        // trigger & duration
        $triggerDashboard = $this->ReadPropertyInteger('DashboardTrigger');
        $openingDashboard = $this->ReadPropertyInteger('DashboardOpening');
        $closingDashboard = $this->ReadPropertyInteger('DashboardClosing');
        $triggerNotify = $this->ReadPropertyInteger('NotificationTrigger');

        // text formates
        $open = $this->ReadPropertyString('TextOpening');
        $close = $this->ReadPropertyString('TextClosing');

        // webfront id & message script
        $webfront = $this->ReadPropertyInteger('InstanceWebfront');
        $msgtitle = $this->ReadPropertyString('TitleMessage');
        $msgscript = $this->ReadPropertyInteger('ScriptMessage');

        // specifier
        $value = [];
        $value['ROOM'] = $this->ReadPropertyString('RoomName');
        $value['TYPE'] = ($state ? $this->Translate('OPEN') : $this->Translate('CLOSE'));
        $value['DATE'] = (date('d.m.Y', time()));
        $value['TIME'] = (date('H:i:s', time()));

        // build message
        $img = 'Window';
        $txt = '';
        $msg = 0;
        $typ = 4;
        $time = 0;
        $sdb = false;
        $swf = false;

        // set the right parameter
        if ($state) {
            $img .= '-0';
            $txt = $this->FormatMessage($value, $open);
            $typ = 2;
            $sdb = ($triggerDashboard & 1);
            $swf = ($triggerNotify & 1);
            $time = $openingDashboard * 60;
        } else {
            $img .= '-100';
            $txt = $this->FormatMessage($value, $close);
            $typ = 0;
            $sdb = ($triggerDashboard & 2);
            $swf = ($triggerNotify & 2);
            $time = $closingDashboard * 60;
        }

        // debug
        $this->LogDebug(__FUNCTION__, 'Image:' . $img . ', Text: ' . $txt . ', SDB:' . $sdb . ', SWF:' . $swf . ', Time:' . $time);
        
        // send notify?
        if ($isNotify && $webfront != 0 && $swf) {
            if ($this->IsWebFrontVisuInstance($webfront)) {
                WFC_PushNotification($webfront, $msgtitle, $txt, $img, 0);
            }
            if ($this->IsTileVisuInstance($webfront)) {
                VISU_PostNotificationEx($webfront, $msgtitle, $txt, $img, 'buzzer', 0);
            }
        }
        
        // send message?
        if ($isDashboard && $msgscript != 0 && $sdb) {
            // remove old message?
            if (!$state) {
                $msg = $this->ReadAttributeInteger('Message');
                if ($msg > 0) {
                    IPS_RunScriptWaitEx($msgscript, ['action' => 'remove', 'number' => $msg]);
                }
            }
            // send new message
            if (IPS_ScriptExists($msgscript)) {
                if ($time > 0) {
                    $msg = IPS_RunScriptWaitEx($msgscript, ['action' => 'add', 'text' => $txt, 'expires' => time() + $time, 'removable' => true, 'type' => $typ, 'image' => $img]);
                } else {
                    $msg = IPS_RunScriptWaitEx($msgscript, ['action' => 'add', 'text' => $txt, 'removable' => true, 'type' => $typ, 'image' => $img]);
                }
            }
            // bookmark message
            $this->WriteAttributeInteger('Message', $msg);
        }
    }

    /**
     * Format a given array to a string.
     *
     * @param array<string,string> $value Weather warning data
     * @param string $format Format string
     *
     * @return string Formatted string
     */
    private function FormatMessage(array $value, $format)
    {
        $output = str_replace('%R', $value['ROOM'], $format);
        $output = str_replace('%M', $value['TYPE'], $output);
        $output = str_replace('%D', $value['DATE'], $output);
        $output = str_replace('%T', $value['TIME'], $output);
        return $output;
    }

    /**
     * Internal state
     *
     * @return void
     */
    private function InternalState(): void
    {
        $reduction = false;
        $sensors = 0;

        // heater state
        $vid = $this->ReadPropertyInteger('Radiator1');
        if ($vid != 0) {
            $oid = @IPS_GetObjectIDByIdent('WINDOW_STATE', $vid);
            if ($oid !== false) {
                $reduction |= GetValue($oid);
            }
        }
        $vid = $this->ReadPropertyInteger('Radiator2');
        if ($vid != 0) {
            $oid = @IPS_GetObjectIDByIdent('WINDOW_STATE', $vid);
            if ($oid !== false) {
                $reduction |= GetValue($oid);
            }
        }
        
        // contact state
        $vid = $this->ReadPropertyInteger('StateVariable');
        if ($vid != 0) {
            $sensors = $sensors + (GetValue($vid) ? 1 : 0);
        }
        $vid = $this->ReadPropertyInteger('StateVariable2');
        if ($vid != 0) {
            $sensors = $sensors + (GetValue($vid) ? 2 : 0);
        }
        
        // deactivate all timer
        $this->SetTimerInterval('DelayTrigger', 0);
        $this->SetTimerInterval('RepeatTrigger', 0);
        $this->SetTimerInterval('SwitchTrigger', 0);
        
        // set sensor state and open/close state
        $this->WriteAttributeInteger('Sensors', $sensors);
        $this->WriteAttributeBoolean('Reduction', $reduction);
        $this->LogDebug(__FUNCTION__, 'Sensors: ' . $sensors . ' Reduction: ' . var_export($reduction, true));
    }
}
