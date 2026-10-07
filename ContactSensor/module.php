<?php

declare(strict_types=1);

/** General functions */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\ContactSensor\DebugHelper;
use Wilkware\ContactSensor\FormHelper;
use Wilkware\ContactSensor\VariableHelper;
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
    use FormHelper;
    use VariableHelper;
    use VersionHelper;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** @var int Min IPS Object ID */
    private const IPS_MIN_ID = 10000;

    /** @var int Status: configured variable does not exist */
    private const STATUS_VARIABLE_MISSING = 201;

    /** @var string Ident of the legacy HomeMatic window state variable */
    private const HM_WINDOW_STATE = 'WINDOW_STATE';

    /** @var int Sensor type: window */
    private const SENSOR_WINDOW = 0;

    /** @var int Sensor type: other or mixed (generic sensor icon) */
    private const SENSOR_OTHER = 4;

    /** @var string Status column: everything fine */
    private const STATE_OK = 'OK';

    /** @var string Status column: variable does not exist */
    private const STATE_MISSING = 'Missing';

    /** @var string Status column: variable has no action */
    private const STATE_NO_ACTION = 'Action required';

    /** @var string Status column: legacy instance selected */
    private const STATE_INSTANCE = 'Please select variable';

    // -------------------------------------------------------------------------
    // Presentations
    // -------------------------------------------------------------------------

    /** @var array<string,mixed> Switch presentation */
    private const TCS_PRESENTATION_SWITCH = [
        'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
    ];

    /** @var array<string,mixed> Temperature difference presentation (Slider) */
    private const TCS_PRESENTATION_DIFFERENCE = [
        'CUSTOM_GRADIENT'     => '[]',
        'DECIMAL_SEPARATOR'   => 'Client',
        'DIGITS'              => 0,
        'GRADIENT_TYPE'       => 0,
        'ICON'                => 'temperature-half',
        'INTERVALS'           => '[]',
        'INTERVALS_ACTIVE'    => false,
        'MAX'                 => 30,
        'MIN'                 => 0,
        'PERCENTAGE'          => false,
        'PREFIX'              => '',
        'PRESENTATION'        => VARIABLE_PRESENTATION_SLIDER,
        'STEP_SIZE'           => 1.0,
        'SUFFIX'              => ' °C',
        'THOUSANDS_SEPARATOR' => '',
        'USAGE_TYPE'          => 0,
    ];

    /** @var array<string,mixed> Repeat interval presentation (Enumeration) */
    private const TCS_PRESENTATION_REPEAT = [
        'DISPLAY'      => 0,
        'ICON'         => 'arrows-rotate',
        'LAYOUT'       => 0,
        'OPTIONS'      => '[{"Caption":"Off","Color":-1,"IconActive":false,"IconValue":"","Value":0},{"Caption":"1 min","Color":-1,"IconActive":false,"IconValue":"","Value":1},{"Caption":"2 min","Color":-1,"IconActive":false,"IconValue":"","Value":2},{"Caption":"3 min","Color":-1,"IconActive":false,"IconValue":"","Value":3},{"Caption":"4 min","Color":-1,"IconActive":false,"IconValue":"","Value":4},{"Caption":"5 min","Color":-1,"IconActive":false,"IconValue":"","Value":5},{"Caption":"10 min","Color":-1,"IconActive":false,"IconValue":"","Value":10},{"Caption":"15 min","Color":-1,"IconActive":false,"IconValue":"","Value":15}]',
        'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
    ];

    /** @var array<string,mixed> Switch back time presentation (Enumeration) */
    private const TCS_PRESENTATION_SWITCHBACK = [
        'DISPLAY'      => 0,
        'ICON'         => 'clock-rotate-left',
        'LAYOUT'       => 0,
        'OPTIONS'      => '[{"Caption":"Off","Color":-1,"IconActive":false,"IconValue":"","Value":0},{"Caption":"10 min","Color":-1,"IconActive":false,"IconValue":"","Value":10},{"Caption":"20 min","Color":-1,"IconActive":false,"IconValue":"","Value":20},{"Caption":"30 min","Color":-1,"IconActive":false,"IconValue":"","Value":30},{"Caption":"40 min","Color":-1,"IconActive":false,"IconValue":"","Value":40},{"Caption":"50 min","Color":-1,"IconActive":false,"IconValue":"","Value":50},{"Caption":"1 h","Color":-1,"IconActive":false,"IconValue":"","Value":60},{"Caption":"2 h","Color":-1,"IconActive":false,"IconValue":"","Value":120},{"Caption":"5 h","Color":-1,"IconActive":false,"IconValue":"","Value":300}]',
        'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
    ];

    /** @var array<string,mixed> Reduction state presentation (Value) */
    private const TCS_PRESENTATION_REDUCTION = [
        'ICON'         => 'window',
        'OPTIONS'      => '[{"Caption":"Inactive","ColorActive":false,"ColorValue":-1,"ContentColorActive":false,"ContentColorValue":-1,"IconActive":true,"IconValue":"window","Value":false},{"Caption":"Active","ColorActive":true,"ColorValue":3828224,"ContentColorActive":false,"ContentColorValue":-1,"IconActive":true,"IconValue":"window-open","Value":true}]',
        'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
    ];

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        // Contact sensors
        $this->RegisterPropertyString('SensorVariables', '[]');

        // Conditional switching
        $this->RegisterPropertyInteger('Delay', 30);
        $this->RegisterPropertyInteger('Level', 0);

        // Heating system
        $this->RegisterPropertyString('RadiatorVariables', '[]');
        $this->RegisterPropertyInteger('ExecScript', 0);

        // Climate
        $this->RegisterPropertyInteger('TempIndoor', 0);
        $this->RegisterPropertyInteger('TempOutdoor', 0);

        // Dashboard
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

        // Legacy properties (v3.x), migrated into the lists and the initial values of the status variables
        $this->RegisterPropertyBoolean('OpenValve', false);
        $this->RegisterPropertyBoolean('TempDiff', false);
        $this->RegisterPropertyInteger('Difference', 10);
        $this->RegisterPropertyBoolean('RepeatCheck', false);
        $this->RegisterPropertyInteger('RepeatTime', 1);
        $this->RegisterPropertyBoolean('SwitchBack', false);
        $this->RegisterPropertyInteger('SwitchTime', 60);
        $this->RegisterPropertyInteger('StateVariable', 0);
        $this->RegisterPropertyInteger('StateVariable2', 0);
        $this->RegisterPropertyInteger('StateVariable3', 0);
        $this->RegisterPropertyInteger('StateVariable4', 0);
        $this->RegisterPropertyInteger('Radiator1', 0);
        $this->RegisterPropertyInteger('Radiator2', 0);

        // Timer
        $this->RegisterTimer('DelayTrigger', 0, "IPS_RequestAction(\$_IPS['TARGET'],'Delay', 0);");
        $this->RegisterTimer('RepeatTrigger', 0, "IPS_RequestAction(\$_IPS['TARGET'],'Repeat', 0);");
        $this->RegisterTimer('SwitchTrigger', 0, "IPS_RequestAction(\$_IPS['TARGET'],'Switch', 0);");

        // Internal state
        $this->RegisterAttributeBoolean('Reduction', false);
        $this->RegisterAttributeInteger('Message', 0);

        // Set visualization type to 1, as we want to offer HTML
        $this->SetVisualizationType(1);
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        parent::Destroy();
    }

    /**
     * Migrates the configuration of older module versions.
     * The four single sensor properties and the two HomeMatic radiator instances become lists.
     *
     * @param string $json Configuration and attributes as JSON
     *
     * @return string Migrated configuration and attributes as JSON
     */
    public function Migrate(string $json): string
    {
        //Never delete this line!
        parent::Migrate($json);

        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['configuration']) || !is_array($data['configuration'])) {
            return $json;
        }
        $config = &$data['configuration'];

        // Sensors
        $sensors = json_decode((string) ($config['SensorVariables'] ?? '[]'), true);
        if (empty($sensors)) {
            $sensors = [];
            foreach (['StateVariable', 'StateVariable2', 'StateVariable3', 'StateVariable4'] as $property) {
                $vid = (int) ($config[$property] ?? 0);
                if ($vid >= self::IPS_MIN_ID) {
                    $sensors[] = ['VariableID' => $vid, 'Type' => self::SENSOR_WINDOW];
                }
                $config[$property] = 0;
            }
            $config['SensorVariables'] = json_encode($sensors);
        } elseif (isset($config['SensorType'])) {
            // Former instance-wide type becomes the type of each sensor
            foreach ($sensors as &$sensor) {
                $sensor['Type'] ??= (int) $config['SensorType'];
            }
            unset($sensor);
            $config['SensorVariables'] = json_encode($sensors);
        }
        unset($config['SensorType']);

        // Radiators (HomeMatic instance => WINDOW_STATE variable)
        $radiators = json_decode((string) ($config['RadiatorVariables'] ?? '[]'), true);
        if (empty($radiators)) {
            $radiators = [];
            foreach (['Radiator1', 'Radiator2'] as $property) {
                $iid = (int) ($config[$property] ?? 0);
                if ($iid >= self::IPS_MIN_ID) {
                    // Objects may not be loaded yet, then keep the instance and resolve it at runtime
                    $vid = @IPS_GetObjectIDByIdent(self::HM_WINDOW_STATE, $iid);
                    $radiators[] = ['VariableID' => ($vid !== false) ? $vid : $iid];
                }
                $config[$property] = 0;
            }
            $config['RadiatorVariables'] = json_encode($radiators);
        }

        return json_encode($data);
    }

    /**
     * The content can be overwritten in order to transfer a self-created configuration page.
     * This way, content can be generated dynamically.
     * In this case, the "form.json" on the file system is completely ignored.
     *
     * @return string Content of the configuration page.
     */
    public function GetConfigurationForm(): string
    {
        // Get form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        // Status column of the lists
        $lists = [
            'SensorVariables'   => false,
            'RadiatorVariables' => true,
        ];
        foreach ($lists as $name => $action) {
            // One entry per list row (also empty ones), the values are merged with the rows by index
            $values = [];
            $list = json_decode($this->ReadPropertyString($name), true);
            foreach (is_array($list) ? $list : [] as $entry) {
                $vid = (int) ($entry['VariableID'] ?? 0);
                $state = ($vid >= self::IPS_MIN_ID) ? $this->GetVariableStatus($vid, $action) : self::STATE_INSTANCE;
                $values[] = ['Status' => $this->Translate($state)];
            }
            $this->ModifyFormElement($form['elements'], $name, function (array &$element) use ($values): void
            {
                $element['values'] = $values;
            });
        }

        // Extract version
        $ins = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($ins['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);
        $version = sprintf('v%s.%d', $lib['Version'], $lib['Build']);
        $this->ModifyFormElement($form['actions'], 'Version', function (array &$element) use ($version): void
        {
            $element['caption'] = $version;
        });

        return json_encode($form);
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
        foreach ($this->GetReferenceList() as $reference) {
            $this->UnregisterReference($reference);
        }

        //Delete all registrations in order to readd them
        foreach ($this->GetMessageList() as $sender => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($sender, $message);
            }
        }

        // Status variables (own objects only)
        $this->MaintainStatusVariables();

        // Other objects are not reliably available before the kernel has started
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            return;
        }

        $missing = false;

        // Sensors: references and update messages = our trigger
        $sensors = $this->GetListVariables('SensorVariables');
        foreach ($sensors as $vid) {
            if (IPS_VariableExists($vid)) {
                $this->RegisterReference($vid);
                $this->RegisterMessage($vid, VM_UPDATE);
            } else {
                $this->LogDebug(__FUNCTION__, 'Sensor variable does not exist: ' . $vid);
                $missing = true;
            }
        }

        // Radiators
        foreach ($this->GetListVariables('RadiatorVariables') as $id) {
            if (IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
            } else {
                $this->LogDebug(__FUNCTION__, 'Radiator variable does not exist: ' . $id);
                $missing = true;
            }
        }

        // Single variables
        foreach (['Level', 'TempIndoor', 'TempOutdoor'] as $property) {
            $vid = $this->ReadPropertyInteger($property);
            if ($vid >= self::IPS_MIN_ID) {
                if (IPS_VariableExists($vid)) {
                    $this->RegisterReference($vid);
                } else {
                    $this->LogDebug(__FUNCTION__, $property . ' variable does not exist: ' . $vid);
                    $missing = true;
                }
            }
        }

        // Scripts
        foreach (['ExecScript', 'ScriptMessage'] as $property) {
            $sid = $this->ReadPropertyInteger($property);
            if ($sid >= self::IPS_MIN_ID) {
                if (IPS_ScriptExists($sid)) {
                    $this->RegisterReference($sid);
                } else {
                    $this->LogDebug(__FUNCTION__, $property . ' script does not exist: ' . $sid);
                    $missing = true;
                }
            }
        }

        // Visualization
        $iid = $this->ReadPropertyInteger('InstanceWebfront');
        if (IPS_InstanceExists($iid)) {
            $this->RegisterReference($iid);
        }

        // Set internal state
        $this->InternalState();
        $this->UpdateTile();

        if ($missing) {
            $this->SetStatus(self::STATUS_VARIABLE_MISSING);
        } elseif (empty($sensors)) {
            $this->SetStatus(IS_INACTIVE);
        } else {
            $this->SetStatus(IS_ACTIVE);
        }
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
        switch ($message) {
            case IPS_KERNELSTARTED:
                $this->LogDebug(__FUNCTION__, 'Kernel started -> apply changes!');
                $this->ApplyChanges();
                break;
            case VM_UPDATE:
                if (!in_array($sender, $this->GetListVariables('SensorVariables'), true)) {
                    $this->LogDebug(__FUNCTION__, 'Sensor #' . $sender . ' unknown!');
                    break;
                }
                // Every value != 0 is open (e.g. rotary handle: 1 = tilted, 2 = open)
                $new = (bool) $data[0];
                $old = (bool) $data[2];
                if (!$data[1] || ($new === $old)) {
                    $this->LogDebug(__FUNCTION__, 'Sensor #' . $sender . ': no state change (' . $this->Stringify($data[0]) . ')');
                    break;
                }
                $this->LogDebug(__FUNCTION__, 'Sensor #' . $sender . ': ' . ($new ? 'OPEN' : 'CLOSE') . ' (' . $this->Stringify($data[0]) . ')');
                if ($new) {
                    $this->Open($sender);
                } else {
                    $this->Close($sender);
                }
                $this->UpdateTile();
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
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . $this->Stringify($value));
        switch ($ident) {
            case 'Delay':
            case 'Repeat':
                $this->Decrease();
                break;
            case 'Switch':
                $this->Restore();
                break;
            case 'TestReduction':
                $this->Reduce();
                break;
            case 'TestRestore':
                $this->Restore();
                break;
            case 'Automatic':
                $this->SetValueBoolean($ident, (bool) $value);
                if (!$value) {
                    // Remember an interrupted process, so it can be resumed when switched on again
                    $running = ($this->GetTimerInterval('DelayTrigger') > 0) || ($this->GetTimerInterval('RepeatTrigger') > 0);
                    $this->SetBuffer('Interrupted', json_encode($running));
                    $this->SetTimerInterval('DelayTrigger', 0);
                    $this->SetTimerInterval('RepeatTrigger', 0);
                } elseif (json_decode($this->GetBuffer('Interrupted')) === true) {
                    $this->SetBuffer('Interrupted', json_encode(false));
                    if ($this->IsAnySensorOpen()) {
                        $this->LogDebug(__FUNCTION__, 'Resume interrupted process!');
                        $this->Open(0);
                    }
                }
                break;
            case 'ValveCheck':
            case 'TemperatureCheck':
                $this->SetValueBoolean($ident, (bool) $value);
                break;
            case 'TemperatureDifference':
            case 'RepeatInterval':
            case 'SwitchBack':
                $this->SetValueInteger($ident, (int) $value);
                break;
            default:
                $this->LogDebug(__FUNCTION__, 'Unknown ident: ' . $ident);
                return;
        }
        $this->UpdateTile();
    }

    /**
     * If the HTML-SDK is to be used, this function must be overwritten in order to return the HTML content.
     *
     * @return string Initial display of a representation via HTML SDK
     */
    public function GetVisualizationTile(): string
    {
        // json_encode a second time to get a correctly quoted and escaped JS string
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';
        $module = file_get_contents(__DIR__ . '/module.html');
        // Important: $initialHandling at the end, as the handleMessage function is only defined in the HTML
        return $module . $initialHandling;
    }

    /**
     * Creates the status variables for conditional switching.
     * New variables are initialized with the (legacy) configuration values.
     *
     * @return void
     */
    private function MaintainStatusVariables(): void
    {
        $switch = self::TCS_PRESENTATION_SWITCH;
        $repeat = $this->TranslatePresentation(self::TCS_PRESENTATION_REPEAT, 'OPTIONS', 'Caption');
        $back = $this->TranslatePresentation(self::TCS_PRESENTATION_SWITCHBACK, 'OPTIONS', 'Caption');
        $reduction = $this->TranslatePresentation(self::TCS_PRESENTATION_REDUCTION, 'OPTIONS', 'Caption');

        // Ident => [name, type, presentation, position, initial value]
        $variables = [
            'Automatic'             => ['Automatic', VARIABLETYPE_BOOLEAN, $switch, 1, true],
            'ValveCheck'            => ['Valve position check', VARIABLETYPE_BOOLEAN, $switch, 2, $this->ReadPropertyBoolean('OpenValve')],
            'TemperatureCheck'      => ['Temperature difference check', VARIABLETYPE_BOOLEAN, $switch, 3, $this->ReadPropertyBoolean('TempDiff')],
            'TemperatureDifference' => ['Temperature difference', VARIABLETYPE_INTEGER, self::TCS_PRESENTATION_DIFFERENCE, 4, $this->ReadPropertyInteger('Difference')],
            'RepeatInterval'        => ['Check conditions repeatedly', VARIABLETYPE_INTEGER, $repeat, 5, $this->ReadPropertyBoolean('RepeatCheck') ? $this->ReadPropertyInteger('RepeatTime') : 0],
            'SwitchBack'            => ['Cancel lowering automatically', VARIABLETYPE_INTEGER, $back, 6, $this->ReadPropertyBoolean('SwitchBack') ? $this->ReadPropertyInteger('SwitchTime') : 0],
            'Reduction'             => ['Reduction', VARIABLETYPE_BOOLEAN, $reduction, 7, $this->ReadAttributeBoolean('Reduction')],
        ];
        foreach ($variables as $ident => $variable) {
            [$name, $type, $presentation, $position, $value] = $variable;
            // A changed type recreates the variable, so it has to be initialized again
            $vid = @$this->GetIDForIdent($ident);
            $exists = IPS_VariableExists($vid) && (IPS_GetVariable($vid)['VariableType'] === $type);
            $this->MaintainVariable($ident, $this->Translate($name), $type, $presentation, $position, true);
            if (!$exists) {
                $this->SetValue($ident, $value);
            }
            if ($ident !== 'Reduction') {
                $this->EnableAction($ident);
            }
        }
    }

    /**
     * Open - Executes if a sensor state changes to OPEN.
     *
     * @param int $sensor Variable ID of the triggered sensor
     *
     * @return void
     */
    private function Open(int $sensor): void
    {
        if (!$this->GetValue('Automatic')) {
            $this->LogDebug(__FUNCTION__, 'Sensor #' . $sensor . ': automatic is switched off!');
            return;
        }

        $delay = $this->ReadPropertyInteger('Delay');
        $running = ($this->GetTimerInterval('DelayTrigger') > 0) || ($this->GetTimerInterval('RepeatTrigger') > 0);
        if ($running || $this->ReadAttributeBoolean('Reduction')) {
            $this->LogDebug(__FUNCTION__, 'Sensor #' . $sensor . ': process already running!');
            return;
        }

        $this->LogDebug(__FUNCTION__, 'Sensor #' . $sensor . ' started the process!');
        if ($delay > 0) {
            $this->StartTimer('DelayTrigger', 1000 * $delay);
        } else {
            $this->Decrease();
        }
    }

    /**
     * Close - Executes if a sensor state changes to CLOSE.
     *
     * @param int $sensor Variable ID of the triggered sensor
     *
     * @return void
     */
    private function Close(int $sensor): void
    {
        if ($this->IsAnySensorOpen()) {
            $this->LogDebug(__FUNCTION__, 'Sensor #' . $sensor . ': another sensor is still open!');
            return;
        }
        $this->LogDebug(__FUNCTION__, 'Sensor #' . $sensor . ' finished the process!');
        $this->Restore();
    }

    /**
     * Checks whether at least one configured sensor reports OPEN.
     *
     * @return bool True if any sensor is open
     */
    private function IsAnySensorOpen(): bool
    {
        foreach ($this->GetListVariables('SensorVariables') as $vid) {
            if (IPS_VariableExists($vid) && (bool) GetValue($vid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Decrease - checks the conditions and lowers the heater temperature.
     *
     * @return void
     */
    private function Decrease(): void
    {
        $this->LogDebug(__FUNCTION__, 'Function was called!');
        $this->SetTimerInterval('DelayTrigger', 0);
        $this->SetTimerInterval('RepeatTrigger', 0);
        $this->SetTimerInterval('SwitchTrigger', 0);

        if (!$this->GetValue('Automatic')) {
            $this->LogDebug(__FUNCTION__, 'Automatic is switched off!');
            return;
        }

        $condition = true;

        // (1) check valve position
        if ($this->GetValue('ValveCheck')) {
            $vid = $this->ReadPropertyInteger('Level');
            if (IPS_VariableExists($vid)) {
                if (GetValue($vid) <= 0) {
                    $this->LogDebug(__FUNCTION__, 'Valve position check is active: valve closed!');
                    $condition = false;
                }
            } else {
                $this->LogDebug(__FUNCTION__, 'Valve position check is active but no position variable available!');
            }
        }

        // (2) check temperature difference
        if ($this->GetValue('TemperatureCheck')) {
            $diff = (int) $this->GetValue('TemperatureDifference');
            $iid = $this->ReadPropertyInteger('TempIndoor');
            $oid = $this->ReadPropertyInteger('TempOutdoor');
            if (IPS_VariableExists($iid) && IPS_VariableExists($oid)) {
                if ((GetValue($iid) - GetValue($oid)) < $diff) {
                    $this->LogDebug(__FUNCTION__, 'Temperature check is active: difference below ' . $diff . '!');
                    $condition = false;
                }
            } else {
                $this->LogDebug(__FUNCTION__, 'Temperature check is active but no temperature variables available!');
            }
        }

        if ($condition) {
            $this->Reduce();
        } else {
            $time = (int) $this->GetValue('RepeatInterval');
            if ($time > 0) {
                $this->StartTimer('RepeatTrigger', $time * 60 * 1000);
            }
        }
    }

    /**
     * Reduce - lowers the heater temperature (without checking conditions).
     *
     * @return void
     */
    private function Reduce(): void
    {
        $this->LogDebug(__FUNCTION__, 'Function was called!');
        $this->SetTimerInterval('DelayTrigger', 0);
        $this->SetTimerInterval('RepeatTrigger', 0);

        $this->SwitchRadiators(true);
        $this->ExecuteScript(true);
        $this->SetReduction(true);

        // Switch back timer
        $time = (int) $this->GetValue('SwitchBack');
        if ($time > 0) {
            $this->StartTimer('SwitchTrigger', $time * 60 * 1000);
        }

        $this->SendMessage(true);
    }

    /**
     * Restore - set heater back to his programm temperature.
     *
     * @return void
     */
    private function Restore(): void
    {
        $this->LogDebug(__FUNCTION__, 'Function was called!');
        $this->SetBuffer('Interrupted', json_encode(false));

        foreach (['DelayTrigger', 'RepeatTrigger', 'SwitchTrigger'] as $timer) {
            if ($this->GetTimerInterval($timer) > 0) {
                $this->SetTimerInterval($timer, 0);
                $this->LogDebug(__FUNCTION__, 'Timer ' . $timer . ' was still active!');
            }
        }

        if (!$this->ReadAttributeBoolean('Reduction')) {
            $this->LogDebug(__FUNCTION__, 'No action necessary, no reduction active!');
            return;
        }

        $this->SwitchRadiators(false);
        $this->ExecuteScript(false);
        $this->SetReduction(false);
        $this->SendMessage(false);
    }

    /**
     * Sets the window state of all radiators via RequestAction.
     *
     * @param bool $open True for OPEN (lower), false for CLOSE (restore)
     *
     * @return void
     */
    private function SwitchRadiators(bool $open): void
    {
        foreach ($this->GetListVariables('RadiatorVariables') as $id) {
            $vid = $this->ResolveRadiatorVariable($id);
            if ($vid === 0) {
                $this->LogMessage('Radiator variable #' . $id . ' does not exist!', KL_ERROR);
                continue;
            }
            $value = match (IPS_GetVariable($vid)['VariableType']) {
                VARIABLETYPE_BOOLEAN => $open,
                VARIABLETYPE_INTEGER => (int) $open,
                VARIABLETYPE_FLOAT   => (float) $open,
                default              => null,
            };
            if ($value === null) {
                $this->LogMessage('Radiator variable #' . $vid . ' has an unsupported type!', KL_ERROR);
                continue;
            }
            $ret = @RequestAction($vid, $value);
            if ($ret === false) {
                $this->LogMessage('Radiator variable #' . $vid . ' could not be switched by RequestAction!', KL_ERROR);
            }
            $this->LogDebug(__FUNCTION__, 'Radiator #' . $vid . ' => ' . ($open ? 'OPEN' : 'CLOSE') . ' (' . $this->Stringify($ret) . ')');
        }
    }

    /**
     * Returns the window state variable of a radiator entry.
     * Migrated entries may still contain the HomeMatic instance instead of the variable.
     *
     * @param int $id Variable or (legacy) instance ID
     *
     * @return int Variable ID or 0 if not found
     */
    private function ResolveRadiatorVariable(int $id): int
    {
        if (IPS_VariableExists($id)) {
            return $id;
        }
        if (IPS_InstanceExists($id)) {
            $vid = @IPS_GetObjectIDByIdent(self::HM_WINDOW_STATE, $id);
            if (IPS_VariableExists($vid)) {
                return $vid;
            }
        }
        return 0;
    }

    /**
     * Executes the additional script.
     *
     * @param bool $open True for OPEN, false for CLOSE
     *
     * @return void
     */
    private function ExecuteScript(bool $open): void
    {
        $script = $this->ReadPropertyInteger('ExecScript');
        if ($script < self::IPS_MIN_ID) {
            return;
        }
        if (IPS_ScriptExists($script)) {
            $rs = IPS_RunScriptEx($script, ['MODUL' => $this->InstanceID, 'WINDOW_STATE' => (int) $open]);
            $this->LogDebug(__FUNCTION__, 'Script execute return value: ' . $this->Stringify($rs));
        } else {
            $this->LogDebug(__FUNCTION__, 'Script #' . $script . ' does not exist!');
        }
    }

    /**
     * Stores the reduction state (attribute and status variable).
     *
     * @param bool $state Reduction active
     *
     * @return void
     */
    private function SetReduction(bool $state): void
    {
        $this->WriteAttributeBoolean('Reduction', $state);
        $this->SetValueBoolean('Reduction', $state);
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
        if (!$isDashboard && !$isNotify) {
            return;
        }

        // trigger & duration
        $triggerDashboard = $this->ReadPropertyInteger('DashboardTrigger');
        $triggerNotify = $this->ReadPropertyInteger('NotificationTrigger');

        // webfront id & message script
        $webfront = $this->ReadPropertyInteger('InstanceWebfront');
        $msgtitle = $this->ReadPropertyString('TitleMessage');
        $msgscript = $this->ReadPropertyInteger('ScriptMessage');

        // specifier
        $value = [
            'ROOM' => $this->ReadPropertyString('RoomName'),
            'TYPE' => ($state ? $this->Translate('OPEN') : $this->Translate('CLOSE')),
            'DATE' => date('d.m.Y', time()),
            'TIME' => date('H:i:s', time()),
        ];

        // set the right parameter
        if ($state) {
            $img = 'Window-0';
            $txt = $this->FormatMessage($value, $this->ReadPropertyString('TextOpening'));
            $typ = 2;
            $sdb = (bool) ($triggerDashboard & 1);
            $swf = (bool) ($triggerNotify & 1);
            $time = $this->ReadPropertyInteger('DashboardOpening') * 60;
        } else {
            $img = 'Window-100';
            $txt = $this->FormatMessage($value, $this->ReadPropertyString('TextClosing'));
            $typ = 0;
            $sdb = (bool) ($triggerDashboard & 2);
            $swf = (bool) ($triggerNotify & 2);
            $time = $this->ReadPropertyInteger('DashboardClosing') * 60;
        }
        $this->LogDebug(__FUNCTION__, 'Image: ' . $img . ', Text: ' . $txt . ', SDB: ' . $this->Stringify($sdb) . ', SWF: ' . $this->Stringify($swf) . ', Time: ' . $time);

        // send notify?
        if ($isNotify && $swf) {
            if ($this->IsWebFrontVisuInstance($webfront)) {
                WFC_PushNotification($webfront, $msgtitle, $txt, $img, 0);
            }
            if ($this->IsTileVisuInstance($webfront)) {
                VISU_PostNotificationEx($webfront, $msgtitle, $txt, $img, 'buzzer', 0);
            }
        }

        // send message?
        if ($isDashboard && IPS_ScriptExists($msgscript)) {
            // remove old message on closing (independent of the trigger)
            if (!$state) {
                $msg = $this->ReadAttributeInteger('Message');
                if ($msg > 0) {
                    IPS_RunScriptWaitEx($msgscript, ['action' => 'remove', 'number' => $msg]);
                    $this->WriteAttributeInteger('Message', 0);
                }
            }
            if ($sdb) {
                $params = ['action' => 'add', 'text' => $txt, 'removable' => true, 'type' => $typ, 'image' => $img];
                if ($time > 0) {
                    $params['expires'] = time() + $time;
                }
                $msg = (int) IPS_RunScriptWaitEx($msgscript, $params);
                $this->WriteAttributeInteger('Message', $msg);
            }
        }
    }

    /**
     * Format a given array to a string.
     *
     * @param array<string,string> $value Message data
     * @param string $format Format string
     *
     * @return string Formatted string
     */
    private function FormatMessage(array $value, string $format): string
    {
        return str_replace(['%R', '%M', '%D', '%T'], [$value['ROOM'], $value['TYPE'], $value['DATE'], $value['TIME']], $format);
    }

    /**
     * Internal state - synchronizes the reduction state with the real device and sensor states.
     *
     * @return void
     */
    private function InternalState(): void
    {
        $this->SetTimerInterval('DelayTrigger', 0);
        $this->SetTimerInterval('RepeatTrigger', 0);
        $this->SetTimerInterval('SwitchTrigger', 0);

        // Reduction derived from the radiators (if configured)
        $reduction = $this->ReadAttributeBoolean('Reduction');
        $radiators = $this->GetListVariables('RadiatorVariables');
        if (!empty($radiators)) {
            $reduction = false;
            foreach ($radiators as $id) {
                $vid = $this->ResolveRadiatorVariable($id);
                if (($vid !== 0) && (bool) GetValue($vid)) {
                    $reduction = true;
                }
            }
        }
        $this->SetReduction($reduction);

        if (empty($this->GetListVariables('SensorVariables'))) {
            return;
        }
        $open = $this->IsAnySensorOpen();
        $this->LogDebug(__FUNCTION__, 'Sensor open: ' . $this->Stringify($open) . ', Reduction: ' . $this->Stringify($reduction));

        // Cancel a stuck reduction or restart the process stopped above for a still open sensor
        if ($reduction && !$open) {
            $this->Restore();
        } elseif ($open && !$reduction && $this->GetValue('Automatic')) {
            $this->LogDebug(__FUNCTION__, 'Sensor still open -> restart process!');
            $this->Open(0);
        }
    }

    /**
     * Starts a timer and remembers its due time for the countdown in the tile.
     *
     * @param string $timer    Name of the timer
     * @param int    $interval Interval in milliseconds
     *
     * @return void
     */
    private function StartTimer(string $timer, int $interval): void
    {
        $this->SetTimerInterval($timer, $interval);
        $due = json_decode($this->GetBuffer('TimerDue'), true);
        if (!is_array($due)) {
            $due = [];
        }
        $due[$timer] = time() + intdiv($interval, 1000);
        $this->SetBuffer('TimerDue', json_encode($due));
    }

    /**
     * Returns the remaining seconds of an active timer.
     *
     * @param string $timer Name of the timer
     *
     * @return int Remaining seconds (0 if inactive)
     */
    private function GetTimerRemaining(string $timer): int
    {
        if ($this->GetTimerInterval($timer) <= 0) {
            return 0;
        }
        $due = json_decode($this->GetBuffer('TimerDue'), true);
        return (is_array($due) && isset($due[$timer])) ? max(0, (int) $due[$timer] - time()) : 0;
    }

    /**
     * Sends the current state to the tile visualization.
     *
     * @return void
     */
    private function UpdateTile(): void
    {
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
        }
    }

    /**
     * Generate a message that updates all elements in the HTML display.
     *
     * @return string JSON encoded message information
     */
    private function GetFullUpdateMessage(): string
    {
        $automatic = (bool) $this->GetValue('Automatic');
        $remaining = 0;
        if ($this->ReadAttributeBoolean('Reduction')) {
            $phase = 'reduced';
            $state = 'Reduction active';
            $remaining = $this->GetTimerRemaining('SwitchTrigger');
        } elseif (!$automatic) {
            $phase = 'off';
            $state = 'Automatic off';
        } elseif ($this->GetTimerInterval('DelayTrigger') > 0) {
            $phase = 'delay';
            $state = 'Reduction starts';
            $remaining = $this->GetTimerRemaining('DelayTrigger');
        } elseif ($this->GetTimerInterval('RepeatTrigger') > 0) {
            $phase = 'waiting';
            $state = 'Waiting for conditions';
            $remaining = $this->GetTimerRemaining('RepeatTrigger');
        } elseif ($this->IsAnySensorOpen()) {
            $phase = 'open';
            $state = 'Open, no reduction';
        } else {
            $phase = 'closed';
            $state = 'Closed';
        }

        // Options first, so that the selected values can be set afterwards
        return json_encode([
            'texts' => [
                'status'                => $this->Translate('Status'),
                'automatic'             => $this->Translate('Automatic'),
                'valve'                 => $this->Translate('Valve'),
                'valveCheck'            => $this->Translate('Valve position check'),
                'temperatureCheck'      => $this->Translate('Temperature difference check'),
                'temperatureDifference' => $this->Translate('Temperature difference'),
                'repeat'                => $this->Translate('Check conditions repeatedly'),
                'switchBack'            => $this->Translate('Cancel lowering automatically'),
            ],
            'RepeatOptions'         => $this->GetPresentationOptions(self::TCS_PRESENTATION_REPEAT),
            'SwitchBackOptions'     => $this->GetPresentationOptions(self::TCS_PRESENTATION_SWITCHBACK),
            'DifferenceMin'         => (int) self::TCS_PRESENTATION_DIFFERENCE['MIN'],
            'DifferenceMax'         => (int) self::TCS_PRESENTATION_DIFFERENCE['MAX'],
            'type'                  => $this->GetSensorType(),
            'phase'                 => $phase,
            'state'                 => $this->Translate($state),
            'remaining'             => $remaining,
            'preset'                => ($phase === 'reduced') ? 0 : $this->ReadPropertyInteger('Delay'),
            'Automatic'             => $automatic,
            'ValveCheck'            => (bool) $this->GetValue('ValveCheck'),
            'TemperatureCheck'      => (bool) $this->GetValue('TemperatureCheck'),
            'TemperatureDifference' => (int) $this->GetValue('TemperatureDifference'),
            'RepeatInterval'        => (int) $this->GetValue('RepeatInterval'),
            'SwitchBack'            => (int) $this->GetValue('SwitchBack'),
        ]);
    }

    /**
     * Extracts the translated options of an enumeration presentation for the tile.
     *
     * @param array<string,mixed> $presentation Enumeration presentation
     *
     * @return list<array{value:int,caption:string}> Options
     */
    private function GetPresentationOptions(array $presentation): array
    {
        $options = [];
        foreach (json_decode($presentation['OPTIONS'], true) as $option) {
            $options[] = ['value' => (int) $option['Value'], 'caption' => $this->Translate($option['Caption'])];
        }
        return $options;
    }

    /**
     * Returns the common type of all configured sensors for the tile icon.
     *
     * @return int Sensor type, SENSOR_OTHER if the types are mixed
     */
    private function GetSensorType(): int
    {
        $types = [];
        $list = json_decode($this->ReadPropertyString('SensorVariables'), true);
        if (is_array($list)) {
            foreach ($list as $entry) {
                if ((int) ($entry['VariableID'] ?? 0) >= self::IPS_MIN_ID) {
                    $types[(int) ($entry['Type'] ?? self::SENSOR_WINDOW)] = true;
                }
            }
        }
        return match (count($types)) {
            0       => self::SENSOR_WINDOW,
            1       => array_key_first($types),
            default => self::SENSOR_OTHER,
        };
    }

    /**
     * Returns the variable IDs of a list property.
     *
     * @param string $property Name of the list property
     *
     * @return list<int> Variable IDs
     */
    private function GetListVariables(string $property): array
    {
        $ids = [];
        $list = json_decode($this->ReadPropertyString($property), true);
        if (is_array($list)) {
            foreach ($list as $entry) {
                $id = (int) ($entry['VariableID'] ?? 0);
                if ($id >= self::IPS_MIN_ID) {
                    $ids[] = $id;
                }
            }
        }
        return $ids;
    }

    /**
     * Returns the status of a configured variable for the form list.
     *
     * @param int  $id     Variable ID
     * @param bool $action Variable must have an action
     *
     * @return string Status text (untranslated)
     */
    private function GetVariableStatus(int $id, bool $action): string
    {
        if (!IPS_VariableExists($id)) {
            return IPS_InstanceExists($id) ? self::STATE_INSTANCE : self::STATE_MISSING;
        }
        if ($action) {
            $var = IPS_GetVariable($id);
            $aid = ($var['VariableCustomAction'] != 0) ? $var['VariableCustomAction'] : $var['VariableAction'];
            if ($aid < self::IPS_MIN_ID) {
                return self::STATE_NO_ACTION;
            }
        }
        return self::STATE_OK;
    }
}
