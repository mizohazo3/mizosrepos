import React, { useState, useEffect, useCallback, useRef } from 'react';
import { 
  Box, 
  Text, 
  VStack, 
  Flex,
  useColorModeValue, 
  Icon,
  HStack
} from '@chakra-ui/react';
import { keyframes } from '@emotion/react';
import { FaPlay, FaStop } from 'react-icons/fa';
import { TimerData } from '../types';
import { TimerService } from '../services/firebase'; // Removed db
// doc and updateDoc are not directly used, TimerService handles DB interactions.
import stopSound from '../sounds/stop.wav';
import { useAudioSettings } from '../contexts/AudioSettingsContext'; // Corrected path

const timerService = new TimerService();

interface TimerProps {
  timer: TimerData;
  onLevelUp: (name: string, newLevel: number) => void;
}

const formatTime = (seconds: number): { hours: string, minutes: string, seconds: string, ms: string } => {
  const totalSeconds = Math.floor(seconds);
  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const secs = totalSeconds % 60;
  const ms = Math.floor((seconds - totalSeconds) * 100);

  return {
    hours: hours.toString().padStart(2, '0'),
    minutes: minutes.toString().padStart(2, '0'),
    seconds: secs.toString().padStart(2, '0'),
    ms: ms.toString().padStart(2, '0')
  };
};

// Commented out old pulse, new activeGlow is defined below
// const pulse = keyframes`
//   0%, 100% { opacity: 0.9; }
//   50% { opacity: 1; }
// `;

const TimerComponent: React.FC<TimerProps> = ({ timer, onLevelUp }) => {
  const [displayTime, setDisplayTime] = useState<number>(0); // This will be updated less frequently
  const { settings } = useAudioSettings();
  
  const startTimeRef = useRef<number>(0);
  const elapsedTimeRef = useRef<number>(0); // Stores the precise elapsed time
  const animationFrameIdRef = useRef<number | null>(null);
  const lastDisplayUpdateTimeRef = useRef<number>(0);

  // Theme colors
  const digitalFontColor = useColorModeValue('gray.800', 'gray.50'); // Ensuring high contrast
  const activeBg = useColorModeValue('green.50', 'green.900'); // Kept for main card background
  const inactiveBg = useColorModeValue('gray.50', 'gray.700'); // Kept for main card background
  const watchBorderColor = useColorModeValue('gray.300', 'gray.600');
  const watchBg = useColorModeValue('white', 'gray.800');
  const digitBg = useColorModeValue('gray.100', 'gray.900');
  // Removed active pulse animation to prevent layout shifts
  const activePulse = 'none';
  const playIconColor = useColorModeValue('green.400', 'green.200'); // Slightly softer
  const stopIconColor = useColorModeValue('red.400', 'red.200'); // Slightly softer
  const separatorColor = useColorModeValue('gray.500', 'gray.400'); // More neutral separator
  const msColor = useColorModeValue('gray.500', 'gray.400');
  const activeBorderColor = useColorModeValue('green.400', 'green.500');

  // Elegant theme adjustments
  const elegantWatchBg = useColorModeValue("linear(to-br, gray.100, gray.200)", "linear(to-br, gray.700, gray.800)");
  const elegantBorderColor = useColorModeValue("gray.300", "gray.600"); // Slightly softer border
  const elegantActiveBorderColor = useColorModeValue("green.400", "green.500"); // Consistent active border

  const activeGlow = keyframes`
    0%, 100% { box-shadow: 0 0 3px 1px ${useColorModeValue('rgba(76, 175, 80, 0.5)', 'rgba(102, 255, 153, 0.5)')}; }
    50% { box-shadow: 0 0 8px 2px ${useColorModeValue('rgba(76, 175, 80, 0.7)', 'rgba(102, 255, 153, 0.7)')}; }
  `;

  useEffect(() => {
    const tick = (timestamp: number) => {
      // Ensure startTimeRef.current is set (which implies timer was started)
      if (!timer.isActive || startTimeRef.current === 0) {
        if (animationFrameIdRef.current) {
          cancelAnimationFrame(animationFrameIdRef.current);
          animationFrameIdRef.current = null;
        }
        // If timer is not active, display accumulated time or 0
        const finalDisplayTime = timer.accumulatedTime || 0;
        setDisplayTime(finalDisplayTime);
        elapsedTimeRef.current = finalDisplayTime;
        return;
      }

      // Calculate elapsed time based on the most recent start time
      const now = Date.now();
      elapsedTimeRef.current = ((now - startTimeRef.current) / 1000) + (timer.accumulatedTime || 0);

      // Update display at max 60fps (every ~16ms)
      if (now - lastDisplayUpdateTimeRef.current > 16) {
        setDisplayTime(elapsedTimeRef.current);
        lastDisplayUpdateTimeRef.current = now;
      }
      
      animationFrameIdRef.current = requestAnimationFrame(tick);
    };

    if (timer.isActive && timer.lastStartTime > 0) {
      // Timer is active and has a valid lastStartTime
      startTimeRef.current = timer.lastStartTime; // This is the start of the CURRENT segment
      // elapsedTimeRef will be calculated in tick, incorporating accumulatedTime
      if (animationFrameIdRef.current) {
        cancelAnimationFrame(animationFrameIdRef.current); // Clear any existing frame
      }
      // Initialize with current time
      const now = Date.now();
      elapsedTimeRef.current = ((now - startTimeRef.current) / 1000) + (timer.accumulatedTime || 0);
      setDisplayTime(elapsedTimeRef.current);
      lastDisplayUpdateTimeRef.current = now;
      animationFrameIdRef.current = requestAnimationFrame(tick);
    } else {
      // Timer is not active or lastStartTime is invalid
      if (animationFrameIdRef.current) {
        cancelAnimationFrame(animationFrameIdRef.current);
        animationFrameIdRef.current = null;
      }
      const finalDisplayTime = timer.accumulatedTime || 0;
      setDisplayTime(finalDisplayTime);
      elapsedTimeRef.current = finalDisplayTime;
      startTimeRef.current = 0; // Reset start time if timer is stopped
    }

    // Cleanup function
    return () => {
      if (animationFrameIdRef.current) {
        cancelAnimationFrame(animationFrameIdRef.current);
        animationFrameIdRef.current = null;
      }
    };
  }, [timer.isActive, timer.lastStartTime]); // Removed timer.accumulatedTime


  // Effect to initialize displayTime when component mounts or timer prop changes significantly
  useEffect(() => {
    if (timer.isActive && timer.lastStartTime > 0) {
      startTimeRef.current = timer.lastStartTime;
      elapsedTimeRef.current = ((Date.now() - timer.lastStartTime) / 1000) + (timer.accumulatedTime || 0);
      setDisplayTime(elapsedTimeRef.current);
    } else {
      const initialDisplay = timer.accumulatedTime || 0;
      setDisplayTime(initialDisplay);
      elapsedTimeRef.current = initialDisplay;
      startTimeRef.current = 0;
    }
    lastDisplayUpdateTimeRef.current = Date.now();
  }, [timer.id]); // Re-initialize if the timer ID itself changes

  const handleStart = useCallback(async () => {
    // The UI will react once timer.isActive and timer.lastStartTime props update from Firebase
    // The useEffect above will then handle starting the animation frame.
    // We set startTimeRef here to ensure the first tick calculation is correct if it runs before prop update.
    // However, the main source of truth for starting the tick loop is timer.isActive from props.
    
    // Optimistically set current start time for immediate feedback if needed,
    // but rely on useEffect for the animation loop.
    // startTimeRef.current = Date.now(); // This would be for a fresh start, not resume.
                                        // For resume, lastStartTime will be set by Firebase.
                                        // accumulatedTime is already part of the timer prop.

    timerService.startTimerInDb(timer.id).catch(error => {
      console.error("Error starting timer in DB:", error);
    });
  }, [timer.id]);

  const playStopSound = useCallback(() => {
    if (
      settings.stopEnabled &&
      document.documentElement.hasAttribute('data-user-interacted')
    ) {
      try {
        const audio = new Audio(stopSound);
        audio.volume = settings.globalVolume;
        const playPromise = audio.play();
        if (playPromise !== undefined) {
          playPromise.catch(error => {
            console.error(`❌ New stop sound playback prevented for timer ${timer.id}:`, error);
          });
        }
      } catch (error) {
        console.error(`❌ Error creating/playing new stop sound instance for timer ${timer.id}:`, error);
      }
    }
  }, [settings.stopEnabled, settings.globalVolume, timer.id]);

  const handleStop = useCallback(async () => {
    playStopSound();
    
    if (animationFrameIdRef.current) {
      cancelAnimationFrame(animationFrameIdRef.current);
      animationFrameIdRef.current = null;
    }
    
    // Calculate final elapsed time before sending to DB
    // This ensures the most accurate time is recorded, especially if there was a delay before this handler ran.
    let finalElapsedTime = elapsedTimeRef.current;
    if (timer.isActive && startTimeRef.current > 0) { // If it was active and had a start time
        finalElapsedTime = ((Date.now() - startTimeRef.current) / 1000) + (timer.accumulatedTime || 0);
    } else { // If it wasn't active, use the current elapsedTimeRef (which should be accumulatedTime)
        finalElapsedTime = timer.accumulatedTime || 0;
    }
    elapsedTimeRef.current = finalElapsedTime; // Update ref one last time
    setDisplayTime(finalElapsedTime); // Update display one last time

    // Non-blocking Firebase update, passing the calculated final time
    // The timerService.stopTimerInDb will need to be adjusted to accept this final time
    // and use it to update accumulatedTime in Firestore.
    timerService.stopTimerInDb(timer.id, finalElapsedTime) 
      .then(result => {
        if (result && result.newLevel > result.oldLevel) {
          onLevelUp(result.timerName, result.newLevel);
        }
      })
      .catch(error => {
        console.error(`Error stopping timer ${timer.id} in DB:`, error);
      });
  }, [timer.id, onLevelUp, playStopSound, timer.isActive, timer.accumulatedTime]); // Added timer.isActive and timer.accumulatedTime

  const handleTimeClick = () => {
    // Use timer.isActive from props to decide action
    if (timer.isActive) {
      handleStop();
    } else {
      handleStart();
    }
  };

  const formattedTime = formatTime(displayTime);

  const TimeDisplay = React.memo(() => {
    const separatorAnimation = React.useMemo(() =>
    timer.isActive ? `${keyframes`
    50% { opacity: 0.3; }
  `} 1s step-start infinite` : 'none',
      [timer.isActive]
    );

    return (
      <HStack spacing="2px" alignItems="baseline" justify="center" minW={{ base: "160px", md: "200px" }} p={1}  transform="translateZ(0)" willChange="transform">
        {/* Hours & Minutes */}
        <Text
          fontFamily="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif"
          fontWeight="600" // Semi-bold for clarity
          fontSize={{ base: "4xl", md: "5xl" }} // Responsive font size
          color={digitalFontColor}
          lineHeight="1"
          letterSpacing="-0.5px" // Slightly tighter
        >
          {formattedTime.hours}:{formattedTime.minutes}
        </Text>
        
        {/* Blinking Separator */}
        <Text
          color={separatorColor}
          mx="2px"
          fontWeight="600"
          fontSize={{ base: "4xl", md: "5xl" }}
          lineHeight="1"
          animation={separatorAnimation}
        >
          :
        </Text>
        
        {/* Seconds */}
        <Text
          fontFamily="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif"
          fontWeight="600"
          fontSize={{ base: "4xl", md: "5xl" }}
          color={digitalFontColor}
          lineHeight="1"
          letterSpacing="-0.5px"
        >
          {formattedTime.seconds}
        </Text>
        
        {/* Milliseconds - less prominent */}
        <Text
          fontFamily="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif"
          fontSize={{ base: "xl", md: "2xl" }}
          ml="3px"
          minWidth={{ base: "34px", md: "40px" }} // Added minWidth to prevent jitter
          textAlign="left" // Ensure text aligns left within the fixed width
          color={msColor}
          fontWeight="500" // Medium weight
          lineHeight="1"
          opacity={0.70} // Slightly more faded
          transform="translateY(-1px)" // Align better with baseline of seconds
        >
          .{formattedTime.ms}
        </Text>
      </HStack>
    );
  });

  return (
    <Box
      width="100%"
      position="relative"
      minHeight={{ base: '110px', md: '130px'}} // Moved here from style prop
      style={{
        transform: 'translateZ(0)', // Promote to a new layer for smoother animations
        height: 'auto', // Allow height to adjust based on content
        // minHeight was here
        overflow: 'hidden' // Clip any overflowing animations
      }}
      data-testid="timer-component"
    >
      {/* Watch face container */}
      <Box
        bg={timer.isActive ? activeBg : inactiveBg}
        borderRadius="lg" // Slightly smaller radius for a sharper look
        borderWidth="1px" // Thinner border
        borderColor={timer.isActive ? activeBorderColor : watchBorderColor}
        boxShadow="md"
        // p={{base: 2, md: 3}} // Padding will be on a new VStack
        position="relative"
        overflow="hidden"
        transition="background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease" // Smoother transitions
        _hover={{
          boxShadow: timer.isActive ? "0 0 15px rgba(72, 187, 120, 0.3)" : "lg" // Enhanced hover shadow, different for active
        }}
      >
        <VStack spacing={{base: 1, md: 2}} p={{base: 2, md: 3}} align="stretch">
          {/* Timer Name Display */}
          <Flex justify="center" align="center" pb={1}>
            <Text
              fontSize={{ base: "md", md: "lg" }}
              fontWeight="semibold"
              color={useColorModeValue("gray.700", "gray.200")}
              noOfLines={1} // Prevent name from wrapping and breaking layout
              title={timer.name} // Show full name on hover if truncated
            >
              {timer.name}
            </Text>
          </Flex>

          {/* Digital time display - Clickable Area */}
          <Flex
          justify="center"
          align="center"
          bgGradient={elegantWatchBg} // Use gradient
          borderRadius="md" // Consistent with inner elements
          p={{base: 1, md: 2}} // Responsive padding
          boxShadow={timer.isActive ? "inner" : "inset 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.05)"} // Softer inner shadow for inactive
          cursor="pointer"
          onClick={handleTimeClick}
          position="relative"
          role="group"
          minHeight={{base: "70px", md: "88px"}} // Increased height for larger text
          display="flex"
          alignItems="center"
          justifyContent="center"
          borderWidth="1px" // Keep border for definition
          transform="translateZ(0)" // GPU acceleration
          borderColor={timer.isActive ? elegantActiveBorderColor : elegantBorderColor} // Use new elegant border colors
          animation={timer.isActive ? `${activeGlow} 1.5s ease-in-out infinite` : 'none'} // Apply glow animation when active
          transition="border-color 0.3s ease, box-shadow 0.3s ease" // For smooth transition of border and shadow
        >
          {/* Play/Stop overlay on hover */}
          <Box
            position="absolute"
            inset="0"
            display="flex"
            alignItems="center"
            justifyContent="center"
            bg={useColorModeValue("blackAlpha.500", "blackAlpha.600")} // Slightly adjusted alpha
            borderRadius="md"
            opacity="0"
            transition="opacity 0.2s ease-in-out" // Smoother transition for icon
            _groupHover={{ opacity: 0.85 }} // Slightly less opaque on hover
            zIndex="1"
            transform="translateZ(0)"
          >
            <Icon as={timer.isActive ? FaStop : FaPlay} fontSize={{base: "2xl", md: "3xl"}} color={timer.isActive ? stopIconColor : playIconColor} />
          </Box>

          <TimeDisplay />
        </Flex>
        </VStack>
      </Box>
    </Box>
  );
};

export default React.memo(TimerComponent);
